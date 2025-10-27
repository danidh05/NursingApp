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

    public function test_admin_can_assign_nurse_to_request(): void
    {
        // Mock OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotification')
            ->andReturn(true);

        // Create a test nurse
        $nurse = \App\Models\Nurse::factory()->create([
            'name' => 'Test Nurse',
            'phone_number' => '1234567890',
            'gender' => 'female'
        ]);

        $request = Request::factory()->create(['status' => Request::STATUS_SUBMITTED]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/requests/{$request->id}", [
                'status' => Request::STATUS_ASSIGNED,
                'nurse_id' => $nurse->id
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => Request::STATUS_ASSIGNED
            ]);

        // Check that the response contains the correct nurse ID
        $responseData = $response->json();
        $this->assertEquals($nurse->id, $responseData['nurse']['id']);
        $this->assertEquals($nurse->name, $responseData['nurse']['name']);
        $this->assertEquals($nurse->phone_number, $responseData['nurse']['phone_number']);
        $this->assertEquals($nurse->gender, $responseData['nurse']['gender']);

        // Verify the nurse was assigned in the database
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'nurse_id' => $nurse->id,
            'status' => Request::STATUS_ASSIGNED
        ]);
    }

    public function test_admin_can_unassign_nurse_from_request(): void
    {
        // Create a test nurse
        $nurse = \App\Models\Nurse::factory()->create();
        
        $request = Request::factory()->create([
            'status' => Request::STATUS_ASSIGNED,
            'nurse_id' => $nurse->id
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/requests/{$request->id}", [
                'nurse_id' => null
            ]);

        $response->assertStatus(200);

        // Check that the response contains null nurse
        $responseData = $response->json();
        $this->assertNull($responseData['nurse']);

        // Verify the nurse was unassigned in the database
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'nurse_id' => null
        ]);
    }

    public function test_complete_nurse_assignment_flow(): void
    {
        // Mock OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotification')
            ->andReturn(true);

        // Step 1: User creates a request (nurse_id will be null by default)
        $response = $this->actingAs($this->user)->postJson('/api/requests', [
            'full_name' => 'John Doe',
            'phone_number' => '1234567890',
            'name' => 'Test Request',
            'location' => 'Test Location',
            'time_type' => Request::TIME_TYPE_FULL,
            'nurse_gender' => 'female',
            'service_ids' => $this->services,
            'problem_description' => 'Test problem',
            'area_id' => $this->user->area_id,
            'scheduled_time' => now()->addDay()->toDateTimeString(),
            'ending_time' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertStatus(201);
        $requestData = $response->json();
        $requestId = $requestData['id'];

        // Verify nurse is null initially
        $this->assertNull($requestData['nurse']);

        // Step 2: Admin assigns a nurse to the request
        $nurse = \App\Models\Nurse::factory()->create([
            'name' => 'Assigned Nurse',
            'phone_number' => '9876543210',
            'gender' => 'female'
        ]);

        $updateResponse = $this->actingAs($this->admin)
            ->putJson("/api/admin/requests/{$requestId}", [
                'status' => Request::STATUS_ASSIGNED,
                'nurse_id' => $nurse->id
            ]);

        $updateResponse->assertStatus(200);
        $updatedData = $updateResponse->json();

        // Verify nurse is now assigned
        $this->assertNotNull($updatedData['nurse']);
        $this->assertEquals($nurse->id, $updatedData['nurse']['id']);
        $this->assertEquals($nurse->name, $updatedData['nurse']['name']);
        $this->assertEquals($nurse->phone_number, $updatedData['nurse']['phone_number']);
        $this->assertEquals($nurse->gender, $updatedData['nurse']['gender']);

        // Step 3: Verify the assignment persists when getting the request
        $getResponse = $this->actingAs($this->user)
            ->getJson("/api/requests/{$requestId}");

        $getResponse->assertStatus(200);
        $getData = $getResponse->json();

        // Verify nurse information is still present
        $this->assertNotNull($getData['nurse']);
        $this->assertEquals($nurse->id, $getData['nurse']['id']);
        $this->assertEquals($nurse->name, $getData['nurse']['name']);

        // Step 4: Admin can unassign the nurse
        $unassignResponse = $this->actingAs($this->admin)
            ->putJson("/api/admin/requests/{$requestId}", [
                'nurse_id' => null
            ]);

        $unassignResponse->assertStatus(200);
        $unassignData = $unassignResponse->json();

        // Verify nurse is now null again
        $this->assertNull($unassignData['nurse']);

        // Step 5: Verify unassignment persists
        $finalGetResponse = $this->actingAs($this->user)
            ->getJson("/api/requests/{$requestId}");

        $finalGetResponse->assertStatus(200);
        $finalData = $finalGetResponse->json();

        // Verify nurse is null
        $this->assertNull($finalData['nurse']);
    }

    public function test_nurse_assignment_validation_fails_for_invalid_nurse_id(): void
    {
        $request = Request::factory()->create();

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/requests/{$request->id}", [
                'nurse_id' => 99999 // Non-existent nurse ID
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nurse_id']);
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