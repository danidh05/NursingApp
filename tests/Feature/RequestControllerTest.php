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
        // Setup roles and create a user
        $userRole = Role::where('name', 'user')->first(); 
        $user = User::factory()->create(['role_id' => $userRole->id]);

        Sanctum::actingAs($user);

        $nurse = Nurse::factory()->create();
        $service = Service::factory()->create();

        $response = $this->postJson('/api/requests', [
            'nurse_id' => $nurse->id,
            'service_id' => $service->id,
            'scheduled_time' => now()->addDays(3), // Add the scheduled time here
            'location' => 'User specified location',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Request created successfully.']);
    }

    /**
     * Test user can view their own request details.
     */
    public function test_user_can_view_own_request()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create([
            'role_id' => $userRole->id, 
            'latitude' => 40.7128, 
            'longitude' => -74.0060
        ]);
    
        $request = UserRequest::factory()->create(['user_id' => $user->id]);
    
        Sanctum::actingAs($user);
    
        $response = $this->getJson("/api/requests/{$request->id}");
    
        $response->assertStatus(200)
                 ->assertJson(['request' => ['id' => $request->id]])
                 ->assertJsonPath('request.user.latitude', (float) $user->latitude) // Cast to float
                 ->assertJsonPath('request.user.longitude', (float) $user->longitude); // Cast to float
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