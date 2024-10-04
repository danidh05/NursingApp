<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Twilio\Rest\Client;
use Mockery;
use Illuminate\Support\Facades\Config;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);


        // // Mock Twilio Client
        // $this->twilioClientMock = Mockery::mock(Client::class);
        // $this->app->instance(Client::class, $this->twilioClientMock);
    }

    public function test_user_can_register()
    {
        // Mock Twilio Client
        $mockTwilio = Mockery::mock(Client::class);
        $mockTwilio->verify = Mockery::mock();
        $mockTwilio->verify->v2 = Mockery::mock();
        $mockTwilio->verify->v2->services = Mockery::mock();
        $mockTwilio->verify->v2->services->shouldReceive('verifications->create')
            ->once()
            ->with('+96178919829', 'sms')  // Assuming you're sending the OTP via SMS or WhatsApp
            ->andReturn((object)['status' => 'pending']);
    
        $this->app->instance(Client::class, $mockTwilio);

        // Send the registration request
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'danidh20052005@gmail.com',
            'phone_number' => '+96178919829',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert that registration is successful and the Twilio mock is called
        $response->assertStatus(201)
                 ->assertJson(['message' => 'User registered successfully. Please check your WhatsApp for the confirmation code.']);
    }

    public function test_user_can_verify_phone()
    {
        // Create a user with a phone number
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '+96178919829',
            'email_verified_at' => null,  // Phone number not yet verified
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Mock Twilio Client for verification check
        $mockTwilio = Mockery::mock(Client::class);
        $mockTwilio->verify = Mockery::mock();
        $mockTwilio->verify->v2 = Mockery::mock();
        $mockTwilio->verify->v2->services = Mockery::mock();
        $mockTwilio->verify->v2->services->shouldReceive('verificationChecks->create')
            ->once()
            ->with('123456', ['to' => '+96178919829'])
            ->andReturn((object)['status' => 'approved']);
    
        $this->app->instance(Client::class, $mockTwilio);

        // Send the verification request
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
        // Create a user with a phone number
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '+96178919829',
            'email_verified_at' => null,
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Mock Twilio Client for verification check
        $mockTwilio = Mockery::mock(Client::class);
        $mockTwilio->verify = Mockery::mock();
        $mockTwilio->verify->v2 = Mockery::mock();
        $mockTwilio->verify->v2->services = Mockery::mock();
        $mockTwilio->verify->v2->services->shouldReceive('verificationChecks->create')
            ->once()
            ->with('654321', ['to' => '+96178919829'])  // Wrong code
            ->andReturn((object)['status' => 'pending']);
    
        $this->app->instance(Client::class, $mockTwilio);

        // Send the wrong verification code
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
        // Create a user with a phone number
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '+96178919829',
            'email_verified_at' => null,
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Mock Twilio Client for verification check
        $mockTwilio = Mockery::mock(Client::class);
        $mockTwilio->verify = Mockery::mock();
        $mockTwilio->verify->v2 = Mockery::mock();
        $mockTwilio->verify->v2->services = Mockery::mock();
        $mockTwilio->verify->v2->services->shouldReceive('verificationChecks->create')
            ->once()
            ->with('123456', ['to' => '+96178919829'])
            ->andReturn((object)['status' => 'pending']);  // Code expired
    
        $this->app->instance(Client::class, $mockTwilio);

        // Send the expired verification code
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
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password123'),
            'email_verified_at' => null, // Phone number not verified
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Your phone number is not verified.']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_register_with_existing_email_or_phone_number()
    {
        // First registration
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password123'),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        // Attempt to register again with the same email
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'john.doe@example.com', // Duplicate email
            'phone_number' => '0987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        // Attempt to register again with the same phone number
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'phone_number' => '1234567890', // Duplicate phone number
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['phone_number']);
    }

   

    public function it_should_resend_verification_code_successfully()
    {
        // Create a new user who is not verified
        $user = User::factory()->create([
            'phone_number' => '+1234567890',
            'email_verified_at' => null, // Not verified yet
        ]);

        // Mock Twilio Verify service
        $this->twilioClientMock->verify->v2->services->shouldReceive('verifications->create')
            ->once()
            ->andReturn(true);

        // Send a POST request to the endpoint
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => $user->phone_number,
        ]);

        // Assert the response is OK
        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification code resent successfully.']);
    }

    public function it_should_return_error_if_user_not_found()
    {
        // Send a POST request with a non-existing phone number
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => '+9876543210',
        ]);

        // Assert that the user not found error is returned
        $response->assertStatus(404)
            ->assertJson(['message' => 'User with this phone number does not exist.']);
    }

    public function it_should_return_error_if_user_already_verified()
    {
        // Create a new user who is already verified
        $user = User::factory()->create([
            'phone_number' => '+1234567890',
            'email_verified_at' => now(), // Already verified
        ]);

        // Send a POST request to the endpoint
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => $user->phone_number,
        ]);

        // Assert that the user is already verified
        $response->assertStatus(400)
            ->assertJson(['message' => 'Phone number is already verified.']);
    }

    public function it_should_handle_twilio_failure()
    {
        // Create a new user who is not verified
        $user = User::factory()->create([
            'phone_number' => '+1234567890',
            'email_verified_at' => null, // Not verified yet
        ]);

        // Mock Twilio Verify service to throw an exception
        $this->twilioClientMock->verify->v2->services->shouldReceive('verifications->create')
            ->once()
            ->andThrow(new \Exception('Twilio Error'));

        // Send a POST request to the endpoint
        $response = $this->postJson('/api/resend-verification-code', [
            'phone_number' => $user->phone_number,
        ]);

        // Assert the Twilio failure error is returned
        $response->assertStatus(500)
            ->assertJson(['message' => 'Failed to send OTP. Please try again later.']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
   

}