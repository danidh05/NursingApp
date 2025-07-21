<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Role;
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
    
    public function setUp(): void
    {
        parent::setUp();

        // Seed roles for testing
        $this->seed(RoleSeeder::class);
    }

    public function test_user_requested_service_event_is_broadcasted()
    {
        // Fake events to capture broadcasting
        Event::fake();
    
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create([
            'role_id' => $userRole->id, 
            'latitude' => 40.7128, 
            'longitude' => -74.0060,
            'name' => 'John Doe', // Add full_name if required
            'phone_number' => '1234567890' // Add phone_number if required
        ]);
        Sanctum::actingAs($user);
    
        $nurse = Nurse::factory()->create(); // Create a nurse for the request
        $services = Service::factory()->count(2)->create(); // Create multiple services
    
        // Prepare the request data, ensuring all required fields are included
        $response = $this->postJson('/api/requests', [
            'nurse_id' => $nurse->id,
            'service_ids' => $services->pluck('id')->toArray(),
            'scheduled_time' => now()->addDays(3),
            'ending_time' => now()->addDays(3)->addHours(2), // Consistent naming
            'location' => 'User specified location',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'time_type' => 'full-time',
            'nurse_gender' => 'male',
            'problem_description' => 'Example problem description',
            'full_name' => 'John Doe',
            'phone_number' => '1234567890',
        ]);
        
    
        // Debugging the response
        // dd($response->json());
    
        // Assert: Check if the response is successful
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'user_id', 'full_name', 'phone_number', 'problem_description',
                     'status', 'time_needed_to_arrive', 'nurse_gender', 'time_type',
                     'scheduled_time', 'location', 'latitude', 'longitude', 'deleted_at',
                     'created_at', 'updated_at', 'user', 'services'
                 ]);
    
        // Assert that the event was dispatched with the correct payload
        Event::assertDispatched(UserRequestedService::class, function ($event) use ($user) {
            return $event->request->user_id === $user->id;
        });
    }
    
    
    
    

    public function test_admin_updated_request_event_is_broadcasted()
    {
        // Fake events to capture broadcasting
        Event::fake();

        // Arrange: Create a request
        $user = User::factory()->create(['role_id' => 2]);
        $admin = User::factory()->create(['role_id' => 1]);
        $nurse = Nurse::factory()->create();
        $services = Service::factory(2)->create();

        $request = UserRequest::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id,
            'status' => 'submitted',  // Use new status constant
        ]);
        $request->services()->attach($services->pluck('id')->toArray()); // Attach services to the request

        // Act: Update the request status to trigger broadcasting
        $response = $this->actingAs($admin)->putJson("/api/admin/requests/{$request->id}", [
            'status' => 'assigned',  // Use new status constant
            'time_needed_to_arrive' => 30
        ]);

        \Log::info("Response status: " . $response->status());
        \Log::info("Response content: " . $response->content());

        // Assert: Check if the response is successful
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'user_id', 'full_name', 'phone_number', 'problem_description',
                     'status', 'time_needed_to_arrive', 'nurse_gender', 'time_type',
                     'scheduled_time', 'location', 'latitude', 'longitude', 'deleted_at',
                     'created_at', 'updated_at', 'user', 'services'
                 ]);

        // Verify that an event was dispatched
        Event::assertDispatched(AdminUpdatedRequest::class, function ($event) {
            return $event->request->status === 'assigned';  // Use new status
        });
    }
}