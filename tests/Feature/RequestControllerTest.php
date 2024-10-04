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
     * Test admin can list all requests.
     */
    public function test_admin_can_list_all_requests()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
    
        Sanctum::actingAs($admin);
    
        // Create multiple requests with users and nurses
        $requests = NurseRequest::factory()->count(3)->create([
            'user_id' => User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id])->id,
        ]);
    
        // Attach services to the requests
        foreach ($requests as $request) {
            $services = Service::factory()->count(2)->create();
            $request->services()->attach($services->pluck('id')->toArray());
        }
    
        $response = $this->getJson('/api/admin/requests');
    
        $response->assertStatus(200)
                 ->assertJsonStructure(['requests' => [['id', 'user_id', 'nurse_id', 'status', 'location', 'services' => [['id', 'name']]]]]);
    }
    
 
    /**
     * Test user can list their own requests.
     */
    public function test_user_can_list_own_requests()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
    
        Sanctum::actingAs($user);
    
        // Create requests for the logged-in user
        $requests = NurseRequest::factory()->count(2)->create(['user_id' => $user->id]);
    
        // Attach services to the requests
        foreach ($requests as $request) {
            $services = Service::factory()->count(2)->create();
            $request->services()->attach($services->pluck('id')->toArray());
        }
    
        $response = $this->getJson('/api/requests');
    
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'requests')
                 ->assertJsonStructure(['requests' => [['id', 'nurse_id', 'status', 'location', 'services' => [['id', 'name']]]]]);
    }
    

    /**
     * Test user can create a request with multiple services.
     */
/**
 * Test user can create a request with multiple services.
 */
public function test_user_can_create_request_with_multiple_services()
{
    // Arrange: Create a role, user, and services
    $userRole = Role::where('name', 'user')->first();
    $user = User::factory()->create([
        'role_id' => $userRole->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
     
    ]);

    Sanctum::actingAs($user);

    // Create multiple services without a nurse since nurse_id is optional on creation
    $services = Service::factory()->count(2)->create();

    // Act: Send a POST request to create a new request without nurse_id
    $response = $this->postJson('/api/requests', [
        'nurse_id' => null, // Optional nurse_id
        'service_ids' => $services->pluck('id')->toArray(),
        'scheduled_time' => now()->addDays(3),
        'ending_time' => now()->addDays(3)->addHours(1), // Add ending time
        'location' => 'User specified location',
        'time_type' => 'full-time',
        'nurse_gender' => 'male',
        'problem_description' => 'User has a specific issue needing attention.',
        'full_name' => 'John Doe', // Include full_name as per new changes
        'phone_number' => '1234567890', // Include phone_number as per new changes
    ]);

    // Assert: Check that the response status is 201 and matches the expected JSON structure
    $response->assertStatus(201)
             ->assertJson([
                 'message' => 'Request created successfully.',
                 'user_location' => [
                     'latitude' => 40.7128,
                     'longitude' => -74.0060,
                 ],
             ])
             ->assertJsonStructure([
                 'request' => [
                     'id', 'user_id', 'nurse_id', 'status', 'location', 'time_type',
                     'problem_description', 'scheduled_time', 'ending_time', 'full_name', 'phone_number',
                     'services' => [
                         ['id', 'name'] // Ensure services are included
                     ],
                 ],
             ]);

    // Additional Assert: Check that the services were correctly attached to the request
    $this->assertDatabaseHas('request_services', [
        'request_id' => $response['request']['id'],
        'service_id' => $services->first()->id,
    ]);
    $this->assertDatabaseHas('request_services', [
        'request_id' => $response['request']['id'],
        'service_id' => $services->last()->id,
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
    
        // Attach services to the request
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
                 ])
                 ->assertJsonMissing(['email_verified_at', 'password']);
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
                 ->assertJson(['message' => 'Request deleted successfully.']);

        $this->assertDatabaseMissing('requests', ['id' => $request->id]);
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
    public function test_user_cannot_create_request_without_location()
    {
        // Arrange: Create a user without location data
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'latitude' => null, // Missing latitude
            'longitude' => null, // Missing longitude
        ]);
    
        Sanctum::actingAs($user);
    
        // Create a nurse and services for the request
        $nurse = Nurse::factory()->create();
        $services = Service::factory()->count(2)->create();
    
        // Act: Attempt to create a request with nurse_gender included
        $response = $this->postJson('/api/requests', [
            'nurse_id' => $nurse->id,
            'service_ids' => $services->pluck('id')->toArray(),
            'scheduled_time' => now()->addDays(3),
            'location' => null, // Set location as null to test location validation
            'time_type' => 'full-time',
            'nurse_gender' => 'male', // Include the required nurse_gender field
        ]);
    
        // Assert: The request should fail with a 422 status code and an appropriate error message
        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Please set your location on the map before creating a request.',
                 ]);
    }
    

}