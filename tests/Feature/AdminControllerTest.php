<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles are seeded before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }
    /**
     * Test admin can create a new user.
     */
    public function test_admin_can_create_user()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Assuming role_id 1 is for admin

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
     * Test admin can view all users.
     */
    public function test_admin_can_view_all_users()
    {
        $admin = User::factory()->create(['role_id' => 1]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'name', 'email']]); // List of users
    }

    // Add more tests for nurse management, request viewing, etc.
}