<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message; // Import the correct class
use Illuminate\Support\Testing\Fakes\MailFake; // Ensure you import the correct namespaces
use Illuminate\Mail\SentMessage; // Correct class for handling raw email assertions
use App\Mail\ConfirmationCodeMail;
use Laravel\Sanctum\Sanctum;





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
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'User registered successfully. Please check your email for the confirmation code.']);
    }

    public function test_user_can_verify_email()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'confirmation_code' => '123456',
            'confirmation_code_expires_at' => now()->addMinutes(10),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);
    
        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'confirmation_code' => '123456',
        ]);
    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email successfully verified.']);
    
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->confirmation_code);
        $this->assertNull($user->fresh()->confirmation_code_expires_at);
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

    public function test_user_cannot_verify_email_with_invalid_code()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'confirmation_code' => '123456',
            'confirmation_code_expires_at' => now()->addMinutes(10),
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'confirmation_code' => '654321', // Wrong code
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Invalid or expired confirmation code.']);
    }

    /**
     * New tests start here
     */

    public function test_user_cannot_login_without_verified_email()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password123'),
            'email_verified_at' => null, // Email not verified
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);
    
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
    
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Your email is not verified.']);
    }

    public function test_user_cannot_verify_email_with_expired_code()
    {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'confirmation_code' => '123456',
            'confirmation_code_expires_at' => Carbon::now()->subMinutes(1), // Expired code
            'role_id' => Role::where('name', 'user')->first()->id,
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'confirmation_code' => '123456',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Invalid or expired confirmation code.']);
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
    public function test_user_receives_confirmation_email_on_registration()
    {
        // Fake the mailer
        Mail::fake();

        // Perform the action: user registration
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert that the registration was successful
        $response->assertStatus(201)
                 ->assertJson(['message' => 'User registered successfully. Please check your email for the confirmation code.']);

        // Assert that the mailable was sent to the correct email
        Mail::assertSent(ConfirmationCodeMail::class, function ($mail) {
            return $mail->hasTo('john.doe@example.com');
        });
    }

  // Example test setup for creating a user with a role_id
public function test_user_can_logout()
{
    // Set up roles and create a user with a role_id
    $role = Role::where('name', 'user')->first(); // Assuming you have a Role model and 'user' role exists
    $user = User::factory()->create(['role_id' => $role->id]); // Set role_id explicitly

    Sanctum::actingAs($user); // Simulate authentication

    // Act: Call the logout route
    $response = $this->postJson('/api/logout');

    // Assert: Check if logout was successful
    $response->assertStatus(200)
             ->assertJson(['message' => 'Logged out successfully']);
}

}