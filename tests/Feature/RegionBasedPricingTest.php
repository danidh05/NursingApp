<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Area;
use App\Models\ServiceAreaPrice;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class RegionBasedPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
    }

    /** @test */
    public function user_can_view_areas_for_registration()
    {
        $response = $this->getJson('/api/areas');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'areas' => [
                        '*' => [
                            'id',
                            'name',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        // Check that Lebanese areas are seeded
        $response->assertJsonFragment(['name' => 'Beirut']);
        $response->assertJsonFragment(['name' => 'Mount Lebanon']);
        $response->assertJsonFragment(['name' => 'North Lebanon']);
    }

    /** @test */
    public function services_show_area_specific_pricing_for_user_with_area()
    {
        // Create areas
        $beirut = Area::where('name', 'Beirut')->first();
        $mountLebanon = Area::where('name', 'Mount Lebanon')->first();

        // Create services
        $service1 = Service::factory()->create(['name' => 'Home Nursing']);
        $service2 = Service::factory()->create(['name' => 'Emergency Care']);

        // Create area-specific prices
        ServiceAreaPrice::create([
            'service_id' => $service1->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service1->id,
            'area_id' => $mountLebanon->id,
            'price' => 120.00
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service2->id,
            'area_id' => $beirut->id,
            'price' => 150.00
        ]);

        // Create user with Beirut area
        $user = User::factory()->create([
            'area_id' => $beirut->id,
            'role_id' => 2 // User role
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'services' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'discount_price',
                            'service_pic',
                            'category_id',
                            'area_name',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        $services = $response->json('services');
        
        // Check that service1 shows Beirut pricing
        $service1Response = collect($services)->firstWhere('id', $service1->id);
        $this->assertEquals(100.00, $service1Response['price']);
        $this->assertEquals('Beirut', $service1Response['area_name']);

        // Check that service2 shows Beirut pricing
        $service2Response = collect($services)->firstWhere('id', $service2->id);
        $this->assertEquals(150.00, $service2Response['price']);
        $this->assertEquals('Beirut', $service2Response['area_name']);
    }

    /** @test */
    public function services_show_different_pricing_for_different_user_areas()
    {
        // Create areas
        $beirut = Area::where('name', 'Beirut')->first();
        $mountLebanon = Area::where('name', 'Mount Lebanon')->first();

        // Create service
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        // Create area-specific prices
        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $mountLebanon->id,
            'price' => 120.00
        ]);

        // Test Beirut user
        $beirutUser = User::factory()->create([
            'area_id' => $beirut->id,
            'role_id' => 2
        ]);

        Sanctum::actingAs($beirutUser);
        $beirutResponse = $this->getJson('/api/services');
        $beirutService = $beirutResponse->json('services')[0];
        $this->assertEquals(100.00, $beirutService['price']);
        $this->assertEquals('Beirut', $beirutService['area_name']);

        // Test Mount Lebanon user
        $mountLebanonUser = User::factory()->create([
            'area_id' => $mountLebanon->id,
            'role_id' => 2
        ]);

        Sanctum::actingAs($mountLebanonUser);
        $mountLebanonResponse = $this->getJson('/api/services');
        $mountLebanonService = $mountLebanonResponse->json('services')[0];
        $this->assertEquals(120.00, $mountLebanonService['price']);
        $this->assertEquals('Mount Lebanon', $mountLebanonService['area_name']);
    }

    /** @test */
    public function services_show_original_price_when_user_has_no_area()
    {
        // Create service with original price
        $service = Service::factory()->create([
            'name' => 'Home Nursing',
            'price' => 80.00
        ]);

        // Create area
        $beirut = Area::where('name', 'Beirut')->first();

        // Create area-specific price
        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        // Create user without area
        $user = User::factory()->create([
            'area_id' => null,
            'role_id' => 2
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
        
        $services = $response->json('services');
        $serviceResponse = $services[0];
        
        // Should show original price when user has no area
        $this->assertEquals(80.00, $serviceResponse['price']);
        $this->assertArrayNotHasKey('area_name', $serviceResponse);
    }

    /** @test */
    public function services_show_original_price_when_no_area_pricing_exists()
    {
        // Create service with original price
        $service = Service::factory()->create([
            'name' => 'Home Nursing',
            'price' => 80.00
        ]);

        // Create area
        $beirut = Area::where('name', 'Beirut')->first();

        // Create user with Beirut area but no pricing for this service
        $user = User::factory()->create([
            'area_id' => $beirut->id,
            'role_id' => 2
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
        
        $services = $response->json('services');
        $serviceResponse = $services[0];
        
        // Should show original price when no area pricing exists
        $this->assertEquals(80.00, $serviceResponse['price']);
        $this->assertArrayNotHasKey('area_name', $serviceResponse);
    }

    /** @test */
    public function user_can_update_profile_with_area()
    {
        $user = User::factory()->create([
            'role_id' => 2
        ]);

        $beirut = Area::where('name', 'Beirut')->first();

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'area_id' => $beirut->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'area_id' => $beirut->id
        ]);
    }

    /** @test */
    public function area_relationships_work_correctly()
    {
        // Create area
        $beirut = Area::where('name', 'Beirut')->first();

        // Create service
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        // Create area price
        $areaPrice = ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        // Test relationships
        $this->assertEquals($beirut->id, $areaPrice->area->id);
        $this->assertEquals($service->id, $areaPrice->service->id);
        $this->assertEquals(100.00, $areaPrice->price);

        // Test service has area prices
        $this->assertCount(1, $service->areaPrices);
        $this->assertEquals(100.00, $service->areaPrices->first()->price);

        // Test area has service prices
        $this->assertCount(1, $beirut->servicePrices);
        $this->assertEquals(100.00, $beirut->servicePrices->first()->price);
    }

    /** @test */
    public function user_can_register_with_area_selection()
    {
        $beirut = Area::where('name', 'Beirut')->first();

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'area_id' => $beirut->id,
        ]);

        $response->assertStatus(201)
                ->assertJson(['message' => 'User registered successfully. Please check your WhatsApp for the OTP.']);

        // Check that user was created with area_id
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '+1234567890',
            'area_id' => $beirut->id,
        ]);
    }

    /** @test */
    public function registration_fails_without_area_id()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // Missing area_id
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['area_id']);
    }

    /** @test */
    public function registration_fails_with_invalid_area_id()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'area_id' => 999, // Invalid area ID
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['area_id']);
    }

    /** @test */
    public function user_can_change_area_in_profile_settings()
    {
        $beirut = Area::where('name', 'Beirut')->first();
        $mountLebanon = Area::where('name', 'Mount Lebanon')->first();

        $user = User::factory()->create([
            'area_id' => $beirut->id,
            'role_id' => 2
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'area_id' => $mountLebanon->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'area_id' => $mountLebanon->id
        ]);

        // Verify the user's area relationship works
        $user->refresh();
        $this->assertEquals('Mount Lebanon', $user->area->name);
    }

    /** @test */
    public function service_pricing_updates_when_user_changes_area()
    {
        $beirut = Area::where('name', 'Beirut')->first();
        $mountLebanon = Area::where('name', 'Mount Lebanon')->first();

        // Create service with different prices per area
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $mountLebanon->id,
            'price' => 120.00
        ]);

        $user = User::factory()->create([
            'area_id' => $beirut->id,
            'role_id' => 2
        ]);

        Sanctum::actingAs($user);

        // Check initial pricing (Beirut)
        $initialResponse = $this->getJson('/api/services');
        $initialService = $initialResponse->json('services')[0];
        $this->assertEquals(100.00, $initialService['price']);
        $this->assertEquals('Beirut', $initialService['area_name']);

        // Change user's area
        $this->putJson("/api/users/{$user->id}", [
            'area_id' => $mountLebanon->id
        ]);

        // Refresh the user to get updated area_id
        $user->refresh();

        // Check updated pricing (Mount Lebanon)
        $updatedResponse = $this->getJson('/api/services');
        $updatedService = $updatedResponse->json('services')[0];
        $this->assertEquals(120.00, $updatedService['price']);
        $this->assertEquals('Mount Lebanon', $updatedService['area_name']);
    }

    /** @test */
    public function admin_can_list_all_service_area_prices()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin role
        $beirut = Area::where('name', 'Beirut')->first();
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/service-area-prices');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'service_area_prices' => [
                        '*' => [
                            'id',
                            'service_id',
                            'area_id',
                            'price',
                            'service' => ['id', 'name'],
                            'area' => ['id', 'name'],
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        $prices = $response->json('service_area_prices');
        $this->assertCount(1, $prices);
        $this->assertEquals(100.00, $prices[0]['price']);
        $this->assertEquals('Home Nursing', $prices[0]['service']['name']);
        $this->assertEquals('Beirut', $prices[0]['area']['name']);
    }

    /** @test */
    public function admin_can_create_service_area_price()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin role
        $beirut = Area::where('name', 'Beirut')->first();
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/service-area-prices', [
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        $response->assertStatus(201)
                ->assertJson(['message' => 'Service area price created successfully.']);

        $this->assertDatabaseHas('service_area_price', [
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);
    }

    /** @test */
    public function admin_cannot_create_duplicate_service_area_price()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin role
        $beirut = Area::where('name', 'Beirut')->first();
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        // Create first price
        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        Sanctum::actingAs($admin);

        // Try to create duplicate
        $response = $this->postJson('/api/admin/service-area-prices', [
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 120.00
        ]);

        $response->assertStatus(422)
                ->assertJson(['message' => 'A price for this service and area combination already exists. Use update instead.']);
    }

    /** @test */
    public function admin_can_update_service_area_price()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin role
        $beirut = Area::where('name', 'Beirut')->first();
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        $serviceAreaPrice = ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/admin/service-area-prices/{$serviceAreaPrice->id}", [
            'price' => 120.00
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Service area price updated successfully.']);

        $this->assertDatabaseHas('service_area_price', [
            'id' => $serviceAreaPrice->id,
            'price' => 120.00
        ]);
    }

    /** @test */
    public function admin_can_delete_service_area_price()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin role
        $beirut = Area::where('name', 'Beirut')->first();
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        $serviceAreaPrice = ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/service-area-prices/{$serviceAreaPrice->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Service area price deleted successfully.']);

        $this->assertDatabaseMissing('service_area_price', [
            'id' => $serviceAreaPrice->id
        ]);
    }

    /** @test */
    public function admin_can_get_prices_for_specific_service()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin role
        $beirut = Area::where('name', 'Beirut')->first();
        $mountLebanon = Area::where('name', 'Mount Lebanon')->first();
        $service = Service::factory()->create(['name' => 'Home Nursing']);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $beirut->id,
            'price' => 100.00
        ]);

        ServiceAreaPrice::create([
            'service_id' => $service->id,
            'area_id' => $mountLebanon->id,
            'price' => 120.00
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/service-area-prices/service/{$service->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'service',
                    'area_prices' => [
                        '*' => [
                            'id',
                            'area_id',
                            'price',
                            'area' => ['id', 'name']
                        ]
                    ]
                ]);

        $areaPrices = $response->json('area_prices');
        $this->assertCount(2, $areaPrices);
        
        // Check Beirut price
        $beirutPrice = collect($areaPrices)->firstWhere('area.name', 'Beirut');
        $this->assertEquals(100.00, $beirutPrice['price']);
        
        // Check Mount Lebanon price
        $mountLebanonPrice = collect($areaPrices)->firstWhere('area.name', 'Mount Lebanon');
        $this->assertEquals(120.00, $mountLebanonPrice['price']);
    }

    /** @test */
    public function non_admin_cannot_access_service_area_price_endpoints()
    {
        $user = User::factory()->create(['role_id' => 2]); // User role
        Sanctum::actingAs($user);

        // Try to access admin endpoints
        $this->getJson('/api/admin/service-area-prices')->assertStatus(403);
        $this->postJson('/api/admin/service-area-prices', [])->assertStatus(403);
        $this->putJson('/api/admin/service-area-prices/1', [])->assertStatus(403);
        $this->deleteJson('/api/admin/service-area-prices/1')->assertStatus(403);
    }
}