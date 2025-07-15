<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use App\Services\TwilioService;
use Mockery;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_user_can_register()
    {
        // Mock TwilioService
        $mockTwilioService = Mockery::mock(TwilioService::class);
        $mockTwilioService->shouldReceive('sendVerificationCode')
            ->once()
            ->with('+96178919829')
            ->andReturn(true);

        $this->app->instance(TwilioService::class, $mockTwilioService);

        // Send the registration request
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'danidh20052005@gmail.com',
            'phone_number' => '+96178919829',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert that registration is successful
        $response->assertStatus(201)
                ->assertJson(['message' => 'User registered successfully. Please check your WhatsApp for the OTP.']);
    }

    public function test_user_can_verify_phone()
    {
        // Create a user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'verify@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Mock TwilioService
        $mockTwilioService = Mockery::mock(TwilioService::class);
        $mockTwilioService->shouldReceive('verifyCode')
            ->once()
            ->with('+96178919829', '123456')
            ->andReturn(true);

        $this->app->instance(TwilioService::class, $mockTwilioService);

        // Send verification request
        $response = $this->postJson('/api/verify-sms', [
            'phone_number' => '+96178919829',
            'verification_code' => '123456',
        ]);

        // Assert the phone verification was successful
        $response->assertStatus(200)
                ->assertJson(['message' => 'Phone number successfully verified.']);

        // Check that the user's phone number is marked as verified
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_cannot_verify_sms_with_invalid_code()
    {
        // Create a user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'invalid@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Mock TwilioService
        $mockTwilioService = Mockery::mock(TwilioService::class);
        $mockTwilioService->shouldReceive('verifyCode')
            ->once()
            ->with('+96178919829', '654321')
            ->andReturn(false);

        $this->app->instance(TwilioService::class, $mockTwilioService);

        // Send verification request with invalid code
        $response = $this->postJson('/api/verify-sms', [
            'phone_number' => '+96178919829',
            'verification_code' => '654321',
        ]);

        // Assert that verification failed
        $response->assertStatus(422)
                ->assertJson(['message' => 'Invalid or expired OTP.']);
    }

    public function test_user_cannot_verify_sms_with_expired_code()
    {
        // Create a user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'expired@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Mock TwilioService
        $mockTwilioService = Mockery::mock(TwilioService::class);
        $mockTwilioService->shouldReceive('verifyCode')
            ->once()
            ->with('+96178919829', '123456')
            ->andReturn(false);

        $this->app->instance(TwilioService::class, $mockTwilioService);

        // Send verification request with expired code
        $response = $this->postJson('/api/verify-sms', [
            'phone_number' => '+96178919829',
            'verification_code' => '123456',
        ]);

        // Assert that verification failed due to expiration
        $response->assertStatus(422)
                ->assertJson(['message' => 'Invalid or expired OTP.']);
    }

    public function test_user_cannot_login_without_verified_phone()
    {
        // Create an unverified user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'unverified@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Attempt to login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                ->assertJson(['message' => 'Your phone number is not verified.']);
    }

    public function test_user_can_login()
    {
        // Create a verified user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'verified@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
            'email_verified_at' => Carbon::now(),
        ]);

        // Attempt to login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        // Create a verified user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'invalid@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
            'email_verified_at' => Carbon::now(),
        ]);

        // Attempt to login with wrong password
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_register_with_existing_email_or_phone_number()
    {
        // Create an existing user
        $existingUser = User::create([
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'phone_number' => '+96178919829',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Attempt to register with the same email
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'existing@example.com',
            'phone_number' => '+96178919830',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Attempt to register with the same phone number
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'new@example.com',
            'phone_number' => '+96178919829',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number']);
    }
}