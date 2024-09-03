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

        // Arrange: Create a user, nurse, and service
        $user = User::factory()->create(['role_id' => 2]); // Assuming 2 is the 'user' role
        $nurse = Nurse::factory()->create();
        $service = Service::factory()->create();

        Sanctum::actingAs($user);

        // Act: User makes a request
        $response = $this->postJson('/api/requests', [
            'nurse_id' => $nurse->id,
            'service_id' => $service->id,
            'location' => '123 Main St',
            'scheduled_time' => now()->addDays(1)->toDateTimeString(),
        ]);

        // Assert: Check if the response is successful
        $response->assertStatus(201)
                 ->assertJson(['message' => 'Request created successfully.']);

        // Assert: The event was dispatched with the correct payload
        Event::assertDispatched(UserRequestedService::class, function ($event) use ($user) {
            return $event->request->user_id === $user->id &&
                   $event->request->nurse_id !== null &&
                   $event->request->service_id !== null;
        });
    }
    public function test_admin_updated_request_event_is_broadcasted()
{
    // Fake events to capture broadcasting
    Event::fake();

    // Arrange: Create an admin user, a regular user, and a request
    $admin = User::factory()->create(['role_id' => 1]); // Assuming 1 is the 'admin' role
    $user = User::factory()->create(['role_id' => 2]); // Regular user role
    $nurse = Nurse::factory()->create();
    $service = Service::factory()->create();

    // Create a request
    $request = UserRequest::factory()->create([
        'user_id' => $user->id,
        'nurse_id' => $nurse->id,
        'service_id' => $service->id,
        'status' => 'pending',
    ]);

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