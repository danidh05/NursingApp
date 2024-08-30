<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** 
     * Test: Admin can list all users.
     */
    public function test_admin_can_list_all_users()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'name', 'email']]);
    }

    /** 
     * Test: Admin can create a new user.
     */
    public function test_admin_can_create_user()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'phone_number' => '0987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'User created successfully']);

        $this->assertDatabaseHas('users', ['email' => 'janedoe@example.com']);
    }

    /** 
     * Test: Admin can delete a user.
     */
    public function test_admin_can_delete_user()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $user = User::factory()->create(['role_id' => Role::where('name', 'user')->first()->id]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** 
     * Test: User can view their own profile.
     */
    public function test_user_can_view_own_profile()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'email' => $user->email,
                     'name' => $user->name,
                 ]);
    }

    /** 
     * Test: User can update their own profile.
     */
    public function test_user_can_update_own_profile()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
    
        Sanctum::actingAs($user);
    
        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated.email@example.com',
        ]);
    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'User updated successfully.']);
    
        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated.email@example.com', $user->fresh()->email);
    }

    /** 
     * Test: User cannot change their role.
     */
    public function test_user_cannot_change_their_role()
    {
        $userRole = Role::where('name', 'user')->first();
        $adminRole = Role::where('name', 'admin')->first();
        
        $user = User::factory()->create(['role_id' => $userRole->id]);
    
        Sanctum::actingAs($user);
    
        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'role_id' => $adminRole->id, // Attempt to change role
        ]);
    
        $response->assertStatus(422);
    
        // Ensure role is still the same
        $this->assertEquals($userRole->id, $user->fresh()->role_id);
    }

    /** 
     * Test: User can submit location on first login.
     */
    public function test_user_can_submit_location_on_first_login()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create([
            'is_first_login' => true,
            'role_id' => $userRole->id,
            'phone_number' => '1234567890',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Location saved successfully.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'latitude' => '40.71280000',
            'longitude' => '-74.00600000',
            'is_first_login' => 0,
        ]);
    }

    /** 
     * Test: User can view dashboard.
     */
    public function test_user_can_view_dashboard()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure(['active_requests', 'recent_services']);
    }
}