<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Nurse;
use App\Models\Service;
use App\Models\Request as UserRequest;
use Laravel\Sanctum\Sanctum;

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
    
        // Ensure each created request has a user with a role_id
        UserRequest::factory()->count(3)->create([
            'user_id' => User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id])->id,
        ]);
    
        $response = $this->getJson('/api/admin/requests');
    
        $response->assertStatus(200)
                 ->assertJsonStructure(['requests' => [['id', 'user_id', 'nurse_id', 'service_id', 'status', 'location']]]);
    }
    

    /**
     * Test user can create a request.
     */
    public function test_user_can_create_request()
    {
        // Setup roles and create a user with latitude and longitude
        $userRole = Role::where('name', 'user')->first(); 
        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'latitude' => 40.7128,   // Example latitude
            'longitude' => -74.0060, // Example longitude
        ]);
    
        Sanctum::actingAs($user);
    
        $nurse = Nurse::factory()->create();
        $service = Service::factory()->create();
    
        $response = $this->postJson('/api/requests', [
            'nurse_id' => $nurse->id,
            'service_id' => $service->id,
            'scheduled_time' => now()->addDays(3), // Add the scheduled time here
            'location' => 'User specified location',
        ]);
    
        // Assert the response
        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Request created successfully.',
                     'user_location' => [
                         'latitude' => 40.7128,   // Expected latitude from the user
                         'longitude' => -74.0060, // Expected longitude from the user
                     ],
                 ])
                 ->assertJsonStructure([
                     'request' => [
                         'id', 'user_id', 'nurse_id', 'service_id', 'status', 'location',
                         // Add other fields from the request that are expected
                     ],
                 ]);
    }
    

    /**
     * Test user can view their own request details.
     */
    public function test_show_returns_request_with_relevant_details()
{
    // Arrange: Create a user, nurse, service, and request
    $user = User::factory()->create([
        'role_id' => 2, // Assuming 'user' role
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

    $service = Service::factory()->create(['name' => 'General Care']);

    // Use the correct alias for the request model
    $request = UserRequest::factory()->create([
        'user_id' => $user->id,
        'nurse_id' => $nurse->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'scheduled_time' => now()->addDays(2),
        'location' => 'Request Location',
    ]);

    // Act: Authenticate as the user and fetch the specific request details
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/requests/{$request->id}");

    // Assert: Check the response status and the structure of returned data
    $response->assertStatus(200)
             ->assertJson([
                 'id' => $request->id,
                 'status' => 'pending',
                 'scheduled_time' => $request->scheduled_time->toISOString(),
                 'location' => 'Request Location',
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
                 'service' => [
                     'id' => $service->id,
                     'name' => 'General Care',
                 ],
             ])
             ->assertJsonMissing(['email_verified_at', 'password']); // Ensure sensitive fields are not included
}

    

    /**
     * Test admin can update a request.
     */
    public function test_admin_can_update_request()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $request = UserRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $adminRole->id])->id]);

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
        $request = UserRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $adminRole->id])->id]);

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
        $request = UserRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $userRole->id])->id]);

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
        $request = UserRequest::factory()->create(['user_id' => User::factory()->create(['role_id' => $userRole->id])->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/admin/requests/{$request->id}");

        $response->assertStatus(403); // Forbidden for users
    }
}