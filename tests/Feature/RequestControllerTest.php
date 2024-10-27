<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Nurse;
use App\Models\Service;
use App\Models\Request as NurseRequest;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class RequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']); // Seed roles
    }

    /**
     * Test admin can list only non-deleted requests.
     */
    public function test_admin_can_list_only_non_deleted_requests()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
    
        Sanctum::actingAs($admin);
    
        // Create non-deleted and soft-deleted requests
        $nonDeletedRequest = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id])->id]);
        $deletedRequest = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id])->id]);
        $deletedRequest->delete(); // Soft delete one request

        // Call the index method for admin
        $response = $this->getJson('/api/requests');

        // Assert that only non-deleted requests are shown to admin
        $response->assertStatus(200)
                ->assertJsonFragment(['id' => $nonDeletedRequest->id])
                 ->assertJsonMissing(['id' => $deletedRequest->id]); // Soft-deleted request should not appear for admin
    }

    /**
     * Test user can list their own requests, including soft-deleted requests.
     */
    public function test_user_can_list_own_requests_including_soft_deleted()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
    
        Sanctum::actingAs($user);
    
        // Create non-deleted and soft-deleted requests for the user
        $nonDeletedRequest = NurseRequest::factory()->create(['user_id' => $user->id]);
        $deletedRequest = NurseRequest::factory()->create(['user_id' => $user->id]);
        $deletedRequest->delete(); // Soft delete one request
    
        // Call the index method for the user
        $response = $this->getJson('/api/requests');
    
        // Assert that both non-deleted and soft-deleted requests are returned for the user
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $nonDeletedRequest->id])
                 ->assertJsonFragment(['id' => $deletedRequest->id]); // Soft-deleted request should still appear for user
    }

    /**
     * Test user can view their own soft-deleted request.
     */
    public function test_user_can_view_soft_deleted_request()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
    
        Sanctum::actingAs($user);
    
        // Create a request and soft delete it
        $request = NurseRequest::factory()->create(['user_id' => $user->id]);
        $request->delete(); // Soft delete the request

        // Call the show method to view the soft-deleted request
        $response = $this->getJson("/api/requests/{$request->id}");

        // Assert that the soft-deleted request can still be viewed by the user
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $request->id]);
    }

    /**
     * Test admin cannot view a soft-deleted request.
     */
    public function test_admin_cannot_view_soft_deleted_request()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => 1]);
    
        Sanctum::actingAs($admin);
    
        // Create a request and soft delete it
        $request = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id])->id]);
        $request->delete(); // Soft delete the request

        // Call the show method to view the soft-deleted request as admin
        $response = $this->getJson("/api/requests/{$request->id}");

        // Assert that admin cannot view the soft-deleted request (should return 404)
        $response->assertStatus(404);
    }

    /**
     * Test user can create a request with multiple services.
     */
    public function test_user_can_create_request_with_multiple_services()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        Sanctum::actingAs($user);

        $services = Service::factory()->count(2)->create();

        $response = $this->postJson('/api/requests', [
            'nurse_id' => null,
            'service_ids' => $services->pluck('id')->toArray(),
            'scheduled_time' => now()->addDays(3),
            'ending_time' => now()->addDays(3)->addHours(1),
            'location' => 'User specified location',
            'time_type' => 'full-time',
            'nurse_gender' => 'male',
            'problem_description' => 'User has a specific issue needing attention.',
            'full_name' => 'John Doe',
            'phone_number' => '1234567890',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'request' => [
                         'id', 'user_id', 'nurse_id', 'status', 'location', 'time_type',
                         'problem_description', 'scheduled_time', 'ending_time', 'full_name', 'phone_number',
                         'services' => [['id', 'name']],
                     ],
                 ]);
    }

    /**
     * Test user can view their own request details with multiple services.
     */
    public function test_show_returns_request_with_relevant_details()
    {
        $user = User::factory()->create([
            'role_id' => 2,
            'name' => 'John Doe',
            'phone_number' => '123456789',
            'location' => 'User Location',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    
        $nurse = Nurse::factory()->create([
            'name' => 'Nurse Jane',
            'phone_number' => '987654321',
            'address' => 'Nurse Address',
            'profile_picture' => 'nurse_profile.jpg',
        ]);
    
        $services = Service::factory()->count(2)->create();
    
        $request = NurseRequest::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id,
            'status' => 'pending',
            'scheduled_time' => now()->addDays(2),
            'location' => 'Request Location',
            'time_type' => 'part-time', // Add the time_type field
        ]);
    
        $request->services()->attach($services->pluck('id')->toArray());
    
        Sanctum::actingAs($user);
        $response = $this->getJson("/api/requests/{$request->id}");
    
        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $request->id,
                     'status' => 'pending',
                     'scheduled_time' => $request->scheduled_time->toISOString(),
                     'location' => 'Request Location',
                     'time_type' => 'part-time', // Check the time_type value
                     'user' => [
                         'id' => $user->id,
                         'name' => 'John Doe',
                         'phone_number' => '123456789',
                         'location' => 'User Location',
                         'latitude' => 40.7128,
                         'longitude' => -74.0060,
                     ],
                     'nurse' => [
                         'id' => $nurse->id,
                         'name' => 'Nurse Jane',
                         'phone_number' => '987654321',
                         'address' => 'Nurse Address',
                         'profile_picture' => 'nurse_profile.jpg',
                     ],
                     'services' => $services->map(fn($service) => [
                         'id' => $service->id,
                         'name' => $service->name,
                     ])->toArray(),
                 ]);
    }

    /**
     * Test admin can update a request.
     */
    public function test_admin_can_update_request()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $request = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $adminRole->id])->id]);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Request updated successfully.']);

        $this->assertEquals('approved', $request->fresh()->status);
    }

    /**
     * Test admin can delete a request.
     */
    public function test_admin_can_delete_request()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $request = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $adminRole->id])->id]);
    
        Sanctum::actingAs($admin);
    
        $response = $this->deleteJson("/api/admin/requests/{$request->id}");
    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Request removed from admin view, but still available to users.']); // Update to the new message
    
        // Ensure the request is soft-deleted
        $this->assertSoftDeleted('requests', ['id' => $request->id]);
    }
    

    /**
     * Test user cannot update a request.
     */
    public function test_user_cannot_update_request()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
        $request = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $userRole->id])->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(403); // Forbidden for users
    }

    /**
     * Test user cannot delete a request.
     */
    public function test_user_cannot_delete_request()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
        $request = NurseRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $userRole->id])->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/admin/requests/{$request->id}");

        $response->assertStatus(403); // Forbidden for users
    }

    /**
     * Test user cannot create request without location.
     */
    public function test_user_cannot_create_request_without_location()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'latitude' => null,
            'longitude' => null,
        ]);

        Sanctum::actingAs($user);

        $services = Service::factory()->count(2)->create();

        $response = $this->postJson('/api/requests', [
            'service_ids' => $services->pluck('id')->toArray(),
            'scheduled_time' => now()->addDays(3),
            'location' => null, // Test missing location
            'time_type' => 'full-time',
            'nurse_gender' => 'male',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Please set your location on the map before creating a request.',
                 ]);
    }

    public function test_user_can_view_request_with_time_needed_to_arrive()
    {
        // Step 1: Create a user and log them in
        $user = User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id]);
        Sanctum::actingAs($user);
        
        // Step 2: Create a new nurse and services
        $nurse = Nurse::factory()->create();
        $services = Service::factory()->count(2)->create();
    
        // Step 3: Create a new request
        $requestData = [
            'nurse_id' => $nurse->id,
            'service_ids' => $services->pluck('id')->toArray(),
            'location' => 'Some Location',
            'scheduled_time' => now()->addMinutes(20),  // Scheduled for later
            'time_type' => 'full-time',
            'problem_description' => 'Test problem',
            'nurse_gender' => 'female',
            'full_name' => 'John Doe',
            'phone_number' => '1234567890',
        ];
    
        // Send a POST request to store the user's request
        $response = $this->postJson('/api/requests', $requestData);
        $response->assertStatus(201);  // Assert request creation
    
        // Step 4: Act as the admin and update the request
        $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->first()->id]);
        Sanctum::actingAs($admin);
    
        // Update request with time_needed_to_arrive
        $updateData = [
            'status' => 'approved',
            'time_needed_to_arrive' => 30,  // Assume 30 minutes
        ];
    
        // Send PUT request to update the request
        $updateResponse = $this->putJson("/api/admin/requests/{$response->json('request.id')}", $updateData);
        $updateResponse->assertStatus(200);
    
        // Step 5: Act as the user again to view the updated request
        Sanctum::actingAs($user);
    
        // Fetch the request
        $viewResponse = $this->getJson("/api/requests/{$response->json('request.id')}");
    
        // Assert that time_needed_to_arrive is present in the response
        $viewResponse->assertStatus(200)
            ->assertJson([
                'time_needed_to_arrive' => 30,
            ]);
    }
    
    

}