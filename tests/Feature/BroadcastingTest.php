<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Service;
use App\Models\Request as UserRequest;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;

use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles for testing
        $this->seed(RoleSeeder::class);
    }

    public function test_user_requested_service_event_is_broadcasted()
    {
        // Fake events to capture broadcasting
        Event::fake();
    
        // Arrange: Create a user, nurse, and services
        $user = User::factory()->create([
            'role_id' => 2, // Assuming 2 is the 'user' role
            'latitude' => 40.7128, // Ensure latitude is set
            'longitude' => -74.0060, // Ensure longitude is set
        ]);
        
        $nurse = Nurse::factory()->create();
        $services = Service::factory()->count(2)->create(); // Create multiple services
    
        Sanctum::actingAs($user);
    
        // Act: User makes a request with multiple services
        $response = $this->postJson('/api/requests', [
            'nurse_id' => $nurse->id,
            'service_ids' => $services->pluck('id')->toArray(), // Attach multiple services
            'location' => '123 Main St',
            'time_type' => 'full-time', // Make sure this matches exactly with the validation
            'scheduled_time' => now()->addDays(1)->toDateTimeString(),
        ]);
    
        // Debug the response to find out why it's failing
        dd($response->json());
    
        // Assert: Check if the response is successful
        $response->assertStatus(201)
                 ->assertJson(['message' => 'Request created successfully.']);
    
        // Assert: The event was dispatched with the correct payload
        Event::assertDispatched(UserRequestedService::class, function ($event) use ($user, $nurse, $services) {
            return $event->request->user_id === $user->id &&
                   $event->request->nurse_id === $nurse->id &&
                   $event->request->services->pluck('id')->sort()->values()->all() === $services->pluck('id')->sort()->values()->all(); // Check the services attached to the request
        });
    }
    
    

    public function test_admin_updated_request_event_is_broadcasted()
    {
        // Fake events to capture broadcasting
        Event::fake();

        // Arrange: Create an admin user, a regular user, a nurse, services, and a request
        $admin = User::factory()->create(['role_id' => 1]); // Assuming 1 is the 'admin' role
        $user = User::factory()->create(['role_id' => 2]); // Regular user role
        $nurse = Nurse::factory()->create();
        $services = Service::factory()->count(2)->create(); // Create multiple services

        // Create a request and attach services
        $request = UserRequest::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id,
            'status' => 'pending',
        ]);
        $request->services()->attach($services->pluck('id')->toArray()); // Attach services to the request

        Sanctum::actingAs($admin);

        // Act: Admin updates the request
        $response = $this->putJson("/api/admin/requests/{$request->id}", [
            'status' => 'approved',
        ]);

        // Assert: Check if the response is successful
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Request updated successfully.']);

        // Assert: The event was dispatched with the correct payload
        Event::assertDispatched(AdminUpdatedRequest::class, function ($event) use ($request) {
            return $event->request->id === $request->id &&
                   $event->request->status === 'approved';
        });
    }
}