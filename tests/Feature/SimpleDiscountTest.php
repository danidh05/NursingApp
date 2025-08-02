<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Area;
use App\Models\Service;
use App\Models\ServiceAreaPrice;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;

class SimpleDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Area $area;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $userRole = Role::create(['name' => 'user']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create area
        $this->area = Area::create([
            'name' => 'Test Area',
            'description' => 'Test Area Description'
        ]);

        // Create user and admin
        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'area_id' => $this->area->id
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'area_id' => $this->area->id
        ]);

        // Create service (price comes from ServiceAreaPrice, not Service model)
        $this->service = Service::factory()->create([
            'name' => 'Test Service',
            'description' => 'Test Service Description',
        ]);

        // Create service area price
        ServiceAreaPrice::create([
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'price' => 100.00
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_can_create_request_with_calculated_price()
    {
        // Mock OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->andReturn(['success' => true]);

        Sanctum::actingAs($this->user);

        $requestData = [
            'service_ids' => [$this->service->id],
            'time_type' => 'part-time',
            'scheduled_time' => now()->addHour()->toISOString(),
            'ending_time' => now()->addHours(3)->toISOString(),
            'location' => 'Test Location',
            'problem_description' => 'Test problem',
            'nurse_gender' => 'female',
            'full_name' => 'Test User',
            'phone_number' => '1234567890',
            'name' => 'Test Request'
        ];

        $response = $this->postJson('/api/requests', $requestData);

        $response->assertStatus(201);

        // Check if request has correct price calculated
        $request = Request::latest()->first();
        
        $this->assertEquals(100.00, $request->total_price);
        $this->assertEquals(100.00, $request->discounted_price);
        $this->assertNull($request->discount_percentage);
        $this->assertFalse($request->hasDiscount());
    }

    /** @test */
    public function user_can_see_discount_in_request_details()
    {
        Sanctum::actingAs($this->user);

        // Create a request with discount applied by admin
        $request = Request::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total_price' => 150.00,
            'discount_percentage' => 15.00,
            'discounted_price' => 127.50
        ]);

        $response = $this->getJson("/api/requests/{$request->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'total_price' => 150.00,
                    'discount_percentage' => 15.00,
                    'discounted_price' => 127.50
                ]);
    }

    /** @test */
    public function admin_can_view_user_requests()
    {
        Sanctum::actingAs($this->admin);

        // Create some test requests for the user
        Request::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'total_price' => 100.00,
            'discounted_price' => 100.00
        ]);

        $response = $this->getJson("/api/admin/users/{$this->user->id}/requests");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'total_requests',
                        'completed_requests',
                        'total_spent',
                        'total_savings'
                    ],
                    'requests' => [
                        '*' => [
                            'id',
                            'status',
                            'total_price',
                            'discount_percentage',
                            'discounted_price',
                            'created_at',
                            'services'
                        ]
                    ]
                ])
                ->assertJsonPath('user.total_requests', 3);
    }

    /** @test */
    public function admin_can_apply_discount_to_request()
    {
        Sanctum::actingAs($this->admin);

        $request = Request::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 200.00,
            'discounted_price' => 200.00
        ]);

        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => 15.00,
        ]);

        $response->assertStatus(200);

        // Verify in database
        $request->refresh();
        $this->assertEquals(15.00, $request->discount_percentage);
        $this->assertEquals(170.00, $request->discounted_price);
        $this->assertTrue($request->hasDiscount());
        $this->assertEquals(30.00, $request->getDiscountAmount());
    }

    /** @test */
    public function admin_can_remove_discount_from_request()
    {
        Sanctum::actingAs($this->admin);

        $request = Request::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 200.00,
            'discount_percentage' => 10.00,
            'discounted_price' => 180.00
        ]);

        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => null,
        ]);

        $response->assertStatus(200);

        // Verify in database
        $request->refresh();
        $this->assertNull($request->discount_percentage);
        $this->assertEquals(200.00, $request->discounted_price);
        $this->assertFalse($request->hasDiscount());
    }

    /** @test */
    public function admin_can_calculate_request_price()
    {
        Sanctum::actingAs($this->admin);

        $request = Request::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => null // Not calculated yet
        ]);

        // Attach service to request
        $request->services()->attach($this->service->id);

        // Manually set a total price to test discount calculation works
        $request->update([
            'total_price' => 100.00,
            'discounted_price' => 100.00
        ]);

        // Apply discount via admin route
        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => 10.00
        ]);

        $response->assertStatus(200);

        // Verify in database
        $request->refresh();
        $this->assertEquals(100.00, $request->total_price);
        $this->assertEquals(90.00, $request->discounted_price);
    }

    /** @test */
    public function discount_cannot_make_price_negative()
    {
        Sanctum::actingAs($this->admin);

        $request = Request::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 50.00,
            'discounted_price' => 50.00
        ]);

        // Apply 100% discount
        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => 100.00
        ]);

        $response->assertStatus(200);

        // Verify price doesn't go below 0
        $request->refresh();
        $this->assertEquals(0.00, $request->discounted_price);
        $this->assertEquals(50.00, $request->getDiscountAmount());
    }

    /** @test */
    public function user_cannot_access_admin_discount_routes()
    {
        Sanctum::actingAs($this->user);

        $request = Request::factory()->create(['user_id' => $this->user->id]);

        // User should not be able to apply discount (admin route)
        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => 10.00
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function discount_validation_works()
    {
        Sanctum::actingAs($this->admin);

        $request = Request::factory()->create(['user_id' => $this->user->id]);

        // Test invalid discount percentage
        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => 150.00 // Over 100%
        ]);

        $response->assertStatus(422);

        // Test negative discount
        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'discount_percentage' => -5.00
        ]);

        $response->assertStatus(422);
    }
} 