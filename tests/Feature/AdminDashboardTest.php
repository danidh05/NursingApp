<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Request;
use App\Models\Nurse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create admin and user
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->admin->load('role');
        $this->user = User::factory()->create(['role_id' => 2]);
        $this->user->load('role');
    }

    public function test_admin_can_view_dashboard(): void
    {
        // Create some test data
        User::factory(5)->create(['role_id' => 2]);
        Request::factory(10)->create();
        Request::factory(3)->create(['status' => 'pending']);
        Nurse::factory(7)->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total_users',
                     'total_requests',
                     'pending_requests',
                     'total_nurses'
                 ]);

        // Get the response data to verify counts are reasonable
        $data = $response->json();
        $this->assertGreaterThanOrEqual(7, $data['total_users']); // At least 7 (5 + admin + user)
        $this->assertGreaterThanOrEqual(13, $data['total_requests']); // At least 13
        $this->assertGreaterThanOrEqual(3, $data['pending_requests']); // At least 3
        $this->assertGreaterThanOrEqual(7, $data['total_nurses']); // At least 7
    }

    public function test_user_cannot_access_admin_dashboard(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_admin_dashboard_shows_zero_counts_when_no_data(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'total_users' => 2, // admin + user
                     'total_requests' => 0,
                     'pending_requests' => 0,
                     'total_nurses' => 0
                 ]);
    }
} 