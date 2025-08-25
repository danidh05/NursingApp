<?php

namespace Tests\Feature;

use App\Models\Request;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RoleSeeder;
use Mockery;

class RequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private array $services;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles and areas are seeded first
        $this->seed(RoleSeeder::class);
        $this->seed(\Database\Seeders\AreaSeeder::class);

        // Get a test area
        $testArea = \App\Models\Area::first();

        // Create admin and regular user with proper roles and area
        $this->admin = User::factory()->create(['role_id' => 1, 'area_id' => $testArea->id]); // Admin role
        $this->user = User::factory()->create(['role_id' => 2, 'area_id' => $testArea->id]);  // User role

        // Load relationships
        $this->admin->load('role');
        $this->user->load('role');

        // Create services for testing
        $this->services = [
            Service::factory()->create()->id,
            Service::factory()->create()->id,
        ];

        // Create service area prices for the test services
        foreach ($this->services as $serviceId) {
            \App\Models\ServiceAreaPrice::create([
                'service_id' => $serviceId,
                'area_id' => $testArea->id,
                'price' => 100.00, // Test price
            ]);
        }

        // Create a test request
        $this->request = Request::factory()
            ->for($this->user)
            ->create(['status' => Request::STATUS_SUBMITTED]); // Use new status constant
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_can_create_request(): void
    {
        // Mock OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->andReturn(['success' => true]);

        $response = $this->actingAs($this->user)->postJson('/api/requests', [
            'full_name' => 'John Doe',
            'phone_number' => '1234567890',
            'name' => 'Emergency Home Care', // Add optional name field
            'location' => 'Test Location',
            'time_type' => Request::TIME_TYPE_FULL,
            'nurse_gender' => 'female',
            'service_ids' => $this->services,
            'problem_description' => 'Test problem',
            'area_id' => $this->user->area_id, // Add required area_id field
            'scheduled_time' => now()->addDay()->toDateTimeString(),
            'ending_time' => now()->addDays(2)->toDateTimeString(),
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                'id',
                'user_id',
                'full_name',
                'name',
                'services'
            ]);

        $this->assertDatabaseHas('requests', [
            'full_name' => 'John Doe',
            'name' => 'Emergency Home Care',
            'user_id' => $this->user->id,
            'status' => Request::STATUS_SUBMITTED // Use new status constant
        ]);
    }

    public function test_user_can_view_own_requests(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/requests');
    
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $request->id]);
    }

    public function test_admin_can_view_all_requests(): void
    {
        $requests = Request::factory(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/requests');

        $response->assertStatus(200);
        foreach ($requests as $request) {
            $response->assertJsonFragment(['id' => $request->id]);
    }
    }

    public function test_user_cannot_update_own_request(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create(['status' => Request::STATUS_SUBMITTED]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/requests/{$request->id}", [
                'full_name' => 'Updated Name',
                'problem_description' => 'Updated Description'
        ]);

        $response->assertStatus(405); // Method not allowed - only admins can update
    }

    public function test_admin_can_update_request_with_time_needed(): void
    {
        // Mock OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->andReturn(['success' => true]);

        $request = Request::factory()->create(['status' => Request::STATUS_SUBMITTED]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/requests/{$request->id}", [
                'status' => Request::STATUS_ASSIGNED,  // Use new status constant
                'time_needed_to_arrive' => 30
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => Request::STATUS_ASSIGNED   // Use new status constant
            ]);
    }

    public function test_unauthorized_user_cannot_view_others_request(): void
    {
        $otherUser = User::factory()->create(['role_id' => 2]);
        $request = Request::factory()
            ->for($otherUser)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/requests/{$request->id}");

        $response->assertStatus(404); // Repository returns 404 for unauthorized access
    }

    public function test_validation_fails_for_invalid_request_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/requests', [
                'full_name' => '', // Required field
                'service_ids' => [] // Empty array
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'service_ids']);
    }

    public function test_admin_can_soft_delete_request(): void
    {
        $request = Request::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/requests/{$request->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Request removed from admin view, but still available to users.']);

        $this->assertSoftDeleted('requests', ['id' => $request->id]);
    }

    public function test_user_can_view_soft_deleted_own_request(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create();

        // Admin soft deletes the request
        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/requests/{$request->id}");
    
        // User should still be able to view it
        $response = $this->actingAs($this->user)
            ->getJson("/api/requests/{$request->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $request->id]);
    }

    public function test_admin_cannot_view_soft_deleted_request(): void
    {
        $request = Request::factory()->create();
    
        // Admin soft deletes the request
        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/requests/{$request->id}");
    
        // Admin should not be able to view it (repository filters out soft deleted for admins)
        $response = $this->actingAs($this->admin)
            ->getJson("/api/requests/{$request->id}");
    
        $response->assertStatus(404); // Correct - admins can't see soft deleted requests
    }
}