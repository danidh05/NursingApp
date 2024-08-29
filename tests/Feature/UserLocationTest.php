<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class UserLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles are seeded before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_location_submission_on_first_login()
    {
        $userRole = Role::where('name', 'user')->first();
    
        $user = User::factory()->create([
            'is_first_login' => true,
            'role_id' => $userRole->id,
            'phone_number' => '1234567890',
        ]);
    
        $response = $this->actingAs($user)->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Location saved successfully.']);
    
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'latitude' => '40.71280000',  // Cast to string to avoid precision issues
            'longitude' => '-74.00600000', // Cast to string to avoid precision issues
            'is_first_login' => false,
        ]);
    }
    
    public function test_location_submission_after_first_login()
    {
        $userRole = Role::where('name', 'user')->first();
    
        $user = User::factory()->create([
            'is_first_login' => false,
            'role_id' => $userRole->id,
            'phone_number' => '1234567890',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    
        $response = $this->actingAs($user)->postJson('/api/submit-location', [
            'latitude' => 34.0522,
            'longitude' => -118.2437,
        ]);
    
        $response->assertStatus(400)
                 ->assertJson(['message' => 'Location already set.']);
    
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'latitude' => '40.71280000', // Cast to string to avoid precision issues
            'longitude' => '-74.00600000', // Cast to string to avoid precision issues
        ]);
    }
    
    public function test_unauthorized_access_to_submit_location()
    {
        $response = $this->postJson('/api/submit-location', [
            'latitude' => 34.0522,
            'longitude' => -118.2437,
        ]);

        $response->assertStatus(401); // 401 Unauthorized
    }
}