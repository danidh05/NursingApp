<?php


namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_admin_can_access_admin_routes()
    {
        $adminRole = Role::create(['name' => 'admin']); // Create the 'admin' role
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $response = $this->actingAs($admin)->get('/api/admin/users');
        $response->assertStatus(200); // OK
    }

    public function test_user_cannot_access_admin_routes()
    {
        $userRole = Role::create(['name' => 'user']); // Create the 'user' role
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($user)->get('/api/admin/users');
        $response->assertStatus(403); // Forbidden
    }

    public function test_user_can_access_user_routes()
    {
        $userRole = Role::create(['name' => 'user']); // Create the 'user' role
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($user)->get('/api/user/dashboard');
        $response->assertStatus(200); // OK
    }
}