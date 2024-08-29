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
        // Ensure roles are seeded before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /**
     * Test viewing user dashboard.
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

    /**
     * Test viewing own profile.
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
     * Test updating own profile.
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
    
        // Corrected the expected response to match the actual response
        $response->assertStatus(200)
                 ->assertJson(['message' => 'User updated successfully.']);  // Note the period at the end
    
        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated.email@example.com', $user->fresh()->email);
    }
    

    /**
     * Test that user cannot change their role.
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
    
        $response->assertStatus(422); // Unprocessable Entity (validation error)
    
        // Ensure role is still the same
        $this->assertEquals($userRole->id, $user->fresh()->role_id);
    }
    

    // Add more user-specific tests here as needed.
    
}