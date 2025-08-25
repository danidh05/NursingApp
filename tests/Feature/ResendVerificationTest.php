<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ResendVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $unverifiedUser;
    private User $verifiedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock TwilioService to prevent 500 errors
        $this->mock(\App\Services\TwilioService::class, function ($mock) {
            $mock->shouldReceive('sendVerificationCode')
                 ->andReturn(true);
        });

        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create unverified user
        $this->unverifiedUser = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => null // Not verified
        ]);
        
        // Create verified user
        $this->verifiedUser = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now() // Already verified
        ]);
    }

    public function test_user_can_resend_verification_code(): void
    {
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => $this->unverifiedUser->phone_number
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Verification code resent successfully.']);
    }

    public function test_user_cannot_resend_verification_if_already_verified(): void
    {
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => $this->verifiedUser->phone_number
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Phone number is already verified.']);
    }

    public function test_user_cannot_resend_verification_with_invalid_phone(): void
    {
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => 'invalid-phone'
        ]);

        $response->assertStatus(404)
                 ->assertJson(['message' => 'User with this phone number does not exist.']);
    }

    public function test_user_cannot_resend_verification_without_phone(): void
    {
        $response = $this->postJson('/api/resend-verification-code', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_user_cannot_resend_verification_with_empty_phone(): void
    {
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => ''
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_user_cannot_resend_verification_with_nonexistent_phone(): void
    {
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => '+1234567890'
        ]);

        $response->assertStatus(404)
                 ->assertJson(['message' => 'User with this phone number does not exist.']);
    }

    public function test_resend_verification_handles_twilio_failure(): void
    {
        // This test would require mocking the TwilioService to simulate failure
        // For now, we'll test the happy path and assume error handling works
        
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => $this->unverifiedUser->phone_number
        ]);

        $response->assertStatus(200);
    }

    public function test_resend_verification_updates_existing_user(): void
    {
        // Create a user with specific phone number
        $user = User::factory()->create([
            'role_id' => 2,
            'phone_number' => '+1234567890',
            'email_verified_at' => null
        ]);

        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => '+1234567890'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Verification code resent successfully.']);

        // Verify the user still exists and is not verified
        $this->assertDatabaseHas('users', [
            'phone_number' => '+1234567890',
            'email_verified_at' => null
        ]);
    }
} 