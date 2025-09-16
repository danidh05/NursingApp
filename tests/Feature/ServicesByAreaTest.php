<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceAreaPrice;
use App\Models\ServiceTranslation;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServicesByAreaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $area;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create user
        $this->user = User::factory()->create();
        
        // Create area
        $this->area = Area::create(['name' => 'Beirut']);
        
        // Create category
        $this->category = Category::create(['name' => 'Home Care']);
    }

    /** @test */
    public function authenticated_user_can_get_services_by_area()
    {
        Sanctum::actingAs($this->user);

        // Create services
        $service1 = Service::create([
            'name' => 'Home Nursing Care',
            'description' => 'Professional nursing care at home',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        $service2 = Service::create([
            'name' => 'Elderly Care',
            'description' => 'Specialized care for elderly patients',
            'price' => 120.00,
            'category_id' => $this->category->id,
        ]);

        // Create area pricing for service1
        ServiceAreaPrice::create([
            'service_id' => $service1->id,
            'area_id' => $this->area->id,
            'price' => 90.00,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'area' => ['id', 'name'],
                'services' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'discount_price',
                        'service_pic',
                        'category_id',
                        'category' => ['id', 'name'],
                        'has_area_pricing',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        // Verify area information
        $response->assertJson([
            'area' => [
                'id' => $this->area->id,
                'name' => $this->area->name,
            ]
        ]);

        // Verify services are returned
        $services = $response->json('services');
        $this->assertCount(2, $services);

        // Find service1 (should have area pricing)
        $service1Data = collect($services)->firstWhere('id', $service1->id);
        $this->assertNotNull($service1Data);
        $this->assertEquals(90.00, $service1Data['price']);
        $this->assertEquals($this->area->name, $service1Data['area_name']);
        $this->assertTrue($service1Data['has_area_pricing']);

        // Find service2 (should have base pricing)
        $service2Data = collect($services)->firstWhere('id', $service2->id);
        $this->assertNotNull($service2Data);
        $this->assertEquals(120.00, $service2Data['price']);
        $this->assertFalse($service2Data['has_area_pricing']);
        $this->assertArrayNotHasKey('area_name', $service2Data);
    }

    /** @test */
    public function services_by_area_returns_404_for_nonexistent_area()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/services/area/999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Area not found']);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_services_by_area()
    {
        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function services_by_area_handles_empty_services_list()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200)
            ->assertJson([
                'area' => [
                    'id' => $this->area->id,
                    'name' => $this->area->name,
                ],
                'services' => []
            ]);
    }

    /** @test */
    public function services_by_area_includes_translations_when_available()
    {
        Sanctum::actingAs($this->user);

        // Create service
        $service = Service::create([
            'name' => 'Home Nursing Care',
            'description' => 'Professional nursing care at home',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        // Create Arabic translation
        ServiceTranslation::create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'رعاية التمريض المنزلية',
        ]);

        // Set Arabic locale
        app()->setLocale('ar');

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $serviceData = $services[0];

        $this->assertEquals('رعاية التمريض المنزلية', $serviceData['name']);
        $this->assertArrayHasKey('translation', $serviceData);
        $this->assertEquals('ar', $serviceData['translation']['locale']);
        $this->assertEquals('رعاية التمريض المنزلية', $serviceData['translation']['name']);
    }

    /** @test */
    public function services_by_area_falls_back_to_original_name_when_no_translation()
    {
        Sanctum::actingAs($this->user);

        // Create service
        $service = Service::create([
            'name' => 'Home Nursing Care',
            'description' => 'Professional nursing care at home',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        // Set Arabic locale (no translation available)
        app()->setLocale('ar');

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $serviceData = $services[0];

        $this->assertEquals('Home Nursing Care', $serviceData['name']);
        $this->assertNull($serviceData['translation']);
    }

    /** @test */
    public function services_by_area_includes_category_information()
    {
        Sanctum::actingAs($this->user);

        // Create service
        $service = Service::create([
            'name' => 'Home Nursing Care',
            'description' => 'Professional nursing care at home',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $serviceData = $services[0];

        $this->assertArrayHasKey('category', $serviceData);
        $this->assertEquals($this->category->id, $serviceData['category']['id']);
        $this->assertEquals($this->category->name, $serviceData['category']['name']);
    }

    /** @test */
    public function services_by_area_handles_multiple_services_with_mixed_pricing()
    {
        Sanctum::actingAs($this->user);

        // Create services
        $service1 = Service::create([
            'name' => 'Service with Area Pricing',
            'description' => 'Has area-specific pricing',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        $service2 = Service::create([
            'name' => 'Service without Area Pricing',
            'description' => 'Uses base pricing',
            'price' => 150.00,
            'category_id' => $this->category->id,
        ]);

        $service3 = Service::create([
            'name' => 'Another Service with Area Pricing',
            'description' => 'Also has area-specific pricing',
            'price' => 200.00,
            'category_id' => $this->category->id,
        ]);

        // Create area pricing for service1 and service3
        ServiceAreaPrice::create([
            'service_id' => $service1->id,
            'area_id' => $this->area->id,
            'price' => 90.00,
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service3->id,
            'area_id' => $this->area->id,
            'price' => 180.00,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $this->assertCount(3, $services);

        // Verify service1 has area pricing
        $service1Data = collect($services)->firstWhere('id', $service1->id);
        $this->assertEquals(90.00, $service1Data['price']);
        $this->assertTrue($service1Data['has_area_pricing']);

        // Verify service2 has base pricing
        $service2Data = collect($services)->firstWhere('id', $service2->id);
        $this->assertEquals(150.00, $service2Data['price']);
        $this->assertFalse($service2Data['has_area_pricing']);

        // Verify service3 has area pricing
        $service3Data = collect($services)->firstWhere('id', $service3->id);
        $this->assertEquals(180.00, $service3Data['price']);
        $this->assertTrue($service3Data['has_area_pricing']);
    }

    /** @test */
    public function services_by_area_handles_discount_prices()
    {
        Sanctum::actingAs($this->user);

        // Create service with discount price
        $service = Service::create([
            'name' => 'Discounted Service',
            'description' => 'Service with discount',
            'price' => 100.00,
            'discount_price' => 80.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $serviceData = $services[0];

        $this->assertEquals(100.00, $serviceData['price']);
        $this->assertEquals(80.00, $serviceData['discount_price']);
    }

    /** @test */
    public function services_by_area_handles_service_pictures()
    {
        Sanctum::actingAs($this->user);

        // Create service with picture
        $service = Service::create([
            'name' => 'Service with Picture',
            'description' => 'Service that has a picture',
            'price' => 100.00,
            'service_pic' => 'https://example.com/service.jpg',
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $serviceData = $services[0];

        $this->assertEquals('https://example.com/service.jpg', $serviceData['service_pic']);
    }

    /** @test */
    public function services_by_area_handles_null_service_pictures()
    {
        Sanctum::actingAs($this->user);

        // Create service without picture
        $service = Service::create([
            'name' => 'Service without Picture',
            'description' => 'Service that has no picture',
            'price' => 100.00,
            'service_pic' => null,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $services = $response->json('services');
        $serviceData = $services[0];

        $this->assertNull($serviceData['service_pic']);
    }

    /** @test */
    public function services_by_area_returns_correct_response_structure()
    {
        Sanctum::actingAs($this->user);

        // Create service
        $service = Service::create([
            'name' => 'Test Service',
            'description' => 'Test description',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'area' => [
                    'id',
                    'name'
                ],
                'services' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'discount_price',
                        'service_pic',
                        'category_id',
                        'category' => [
                            'id',
                            'name'
                        ],
                        'has_area_pricing',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function services_by_area_handles_large_number_of_services()
    {
        Sanctum::actingAs($this->user);

        // Create 10 services
        $services = [];
        for ($i = 1; $i <= 10; $i++) {
            $services[] = Service::create([
                'name' => "Service $i",
                'description' => "Description for service $i",
                'price' => 100.00 + $i,
                'category_id' => $this->category->id,
            ]);
        }

        // Create area pricing for some services
        for ($i = 0; $i < 5; $i++) {
            ServiceAreaPrice::create([
                'service_id' => $services[$i]->id,
                'area_id' => $this->area->id,
                'price' => 90.00 + $i,
            ]);
        }

        $response = $this->getJson("/api/services/area/{$this->area->id}");

        $response->assertStatus(200);

        $responseServices = $response->json('services');
        $this->assertCount(10, $responseServices);

        // Verify some have area pricing, some don't
        $withAreaPricing = collect($responseServices)->where('has_area_pricing', true);
        $withoutAreaPricing = collect($responseServices)->where('has_area_pricing', false);

        $this->assertCount(5, $withAreaPricing);
        $this->assertCount(5, $withoutAreaPricing);
    }

    /** @test */
    public function services_by_area_handles_invalid_area_id_format()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/services/area/invalid');

        // Laravel returns 500 for invalid route parameters, not 404
        $response->assertStatus(500);
    }

    /** @test */
    public function services_by_area_works_with_different_areas()
    {
        Sanctum::actingAs($this->user);

        // Create another area
        $area2 = Area::create(['name' => 'Tripoli']);

        // Create service
        $service = Service::create([
            'name' => 'Test Service',
            'description' => 'Test description',
            'price' => 100.00,
            'category_id' => $this->category->id,
        ]);

        // Create area pricing for both areas
        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $this->area->id,
            'price' => 90.00,
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $area2->id,
            'price' => 95.00,
        ]);

        // Test first area
        $response1 = $this->getJson("/api/services/area/{$this->area->id}");
        $response1->assertStatus(200);
        $service1Data = $response1->json('services')[0];
        $this->assertEquals(90.00, $service1Data['price']);
        $this->assertEquals($this->area->name, $service1Data['area_name']);

        // Test second area
        $response2 = $this->getJson("/api/services/area/{$area2->id}");
        $response2->assertStatus(200);
        $service2Data = $response2->json('services')[0];
        $this->assertEquals(95.00, $service2Data['price']);
        $this->assertEquals($area2->name, $service2Data['area_name']);
    }
}