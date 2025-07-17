<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class LocationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $firstLoginUser;
    private User $existingUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create user on first login
        $this->firstLoginUser = User::factory()->create([
            'role_id' => 2,
            'is_first_login' => true,
            'latitude' => null,
            'longitude' => null
        ]);
        
        // Create user who has already submitted location
        $this->existingUser = User::factory()->create([
            'role_id' => 2,
            'is_first_login' => false,
            'latitude' => 40.7128,
            'longitude' => -74.0060
        ]);
    }

    public function test_user_can_submit_location_on_first_login(): void
    {
        Sanctum::actingAs($this->firstLoginUser);

        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Location saved successfully.']);

        // Verify location was saved and first login flag was updated
        $this->firstLoginUser->refresh();
        $this->assertEquals(40.7128, $this->firstLoginUser->latitude);
        $this->assertEquals(-74.0060, $this->firstLoginUser->longitude);
        $this->assertFalse($this->firstLoginUser->is_first_login);
    }

    public function test_user_cannot_submit_location_after_first_login(): void
    {
        Sanctum::actingAs($this->existingUser);

        $response = $this->postJson('/api/submit-location', [
            'latitude' => 34.0522,
            'longitude' => -118.2437
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Location already set.']);

        // Verify location was not changed
        $this->existingUser->refresh();
        $this->assertEquals(40.7128, $this->existingUser->latitude);
        $this->assertEquals(-74.0060, $this->existingUser->longitude);
    }

    public function test_location_validation_works(): void
    {
        Sanctum::actingAs($this->firstLoginUser);

        // Test missing latitude
        $response = $this->postJson('/api/submit-location', [
            'longitude' => -74.0060
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['latitude']);

        // Test missing longitude
        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['longitude']);

        // Test invalid latitude (too high)
        $response = $this->postJson('/api/submit-location', [
            'latitude' => 91.0,
            'longitude' => -74.0060
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['latitude']);

        // Test invalid latitude (too low)
        $response = $this->postJson('/api/submit-location', [
            'latitude' => -91.0,
            'longitude' => -74.0060
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['latitude']);

        // Test invalid longitude (too high)
        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => 181.0
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['longitude']);

        // Test invalid longitude (too low)
        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => -181.0
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['longitude']);
    }

    public function test_location_accepts_valid_coordinates(): void
    {
        Sanctum::actingAs($this->firstLoginUser);

        // Test valid coordinates at boundaries
        $validCoordinates = [
            ['latitude' => 90.0, 'longitude' => 180.0],
            ['latitude' => -90.0, 'longitude' => -180.0],
            ['latitude' => 0.0, 'longitude' => 0.0],
            ['latitude' => 40.7128, 'longitude' => -74.0060],
            ['latitude' => -33.8688, 'longitude' => 151.2093]
        ];

        foreach ($validCoordinates as $index => $coords) {
            // Create a new user for each test to avoid "already set location" error
            $user = User::factory()->create([
                'role_id' => 2,
                'is_first_login' => true,
                'latitude' => null,
                'longitude' => null
            ]);
            
            Sanctum::actingAs($user);
            
            $response = $this->postJson('/api/submit-location', $coords);
            $response->assertStatus(200);
        }
    }

    public function test_unauthorized_user_cannot_submit_location(): void
    {
        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060
        ]);

        $response->assertStatus(401);
    }

    public function test_location_submission_updates_user_model(): void
    {
        Sanctum::actingAs($this->firstLoginUser);

        $originalLatitude = $this->firstLoginUser->latitude;
        $originalLongitude = $this->firstLoginUser->longitude;
        $originalFirstLogin = $this->firstLoginUser->is_first_login;

        $response = $this->postJson('/api/submit-location', [
            'latitude' => 34.0522,
            'longitude' => -118.2437
        ]);

        $response->assertStatus(200);

        // Refresh the model from database
        $this->firstLoginUser->refresh();

        // Verify changes
        $this->assertNotEquals($originalLatitude, $this->firstLoginUser->latitude);
        $this->assertNotEquals($originalLongitude, $this->firstLoginUser->longitude);
        $this->assertNotEquals($originalFirstLogin, $this->firstLoginUser->is_first_login);
        $this->assertEquals(34.0522, $this->firstLoginUser->latitude);
        $this->assertEquals(-118.2437, $this->firstLoginUser->longitude);
        $this->assertFalse($this->firstLoginUser->is_first_login);
    }

    public function test_location_submission_handles_decimal_precision(): void
    {
        Sanctum::actingAs($this->firstLoginUser);

        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128123456789,
            'longitude' => -74.0060123456789
        ]);

        $response->assertStatus(200);

        $this->firstLoginUser->refresh();
        // Check that the values are stored with reasonable precision (database may truncate)
        $this->assertEquals(
            round(40.7128123456789, 6),
            round($this->firstLoginUser->latitude, 6)
        );
        $this->assertEquals(
            round(-74.0060123456789, 6),
            round($this->firstLoginUser->longitude, 6)
        );
    }

    public function test_location_submission_requires_user_role(): void
    {
        // Create admin user with first login
        $adminUser = User::factory()->create([
            'role_id' => 1,
            'is_first_login' => true,
            'latitude' => null,
            'longitude' => null
        ]);

        Sanctum::actingAs($adminUser);

        $response = $this->postJson('/api/submit-location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060
        ]);

        // This should fail because the route is protected by 'role:user' middleware
        $response->assertStatus(403);
    }
} 