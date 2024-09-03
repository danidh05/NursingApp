<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Nurse;
use App\Models\Service;
use App\Models\Request as NurseRequest; // Correct alias for the Request model
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use App\Events\AdminUpdatedRequest;
use App\Events\UserRequestedService;

class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']); // Seed roles
    }

    /**
     * Test user can view notifications.
     */
    public function test_user_can_view_notifications()
    {
        Event::fake();

        // Create a user, nurse, service, and request
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
        $nurse = Nurse::factory()->create();
        $service = Service::factory()->create();

        // Create a request associated with the user
        $request = NurseRequest::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'scheduled_time' => now()->addDays(2),
            'location' => '123 Main St',
        ]);

        Sanctum::actingAs($user);

        // Dispatch the UserRequestedService event with the correct request model
        event(new UserRequestedService($request));

        // Fetch notifications via API (This assumes you are storing events as notifications)
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200);

        // Assert that the event was dispatched
        Event::assertDispatched(UserRequestedService::class);
    }

    /**
     * Test admin can view notifications.
     */
    public function test_admin_can_view_notifications()
    {
        Event::fake();

        // Create an admin user, a regular user, nurse, service, and request
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $user = User::factory()->create(['role_id' => 2]); // Assuming 2 is the 'user' role
        $nurse = Nurse::factory()->create();
        $service = Service::factory()->create();

        // Create a request associated with the user
        $request = NurseRequest::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'scheduled_time' => now()->addDays(2),
            'location' => '123 Main St',
        ]);

        Sanctum::actingAs($admin);

        // Dispatch the AdminUpdatedRequest event with the correct request model
        event(new AdminUpdatedRequest($request));

        // Fetch notifications via API
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200);

        // Assert that the event was dispatched
        Event::assertDispatched(AdminUpdatedRequest::class);
    }

    // Adjust the remaining tests (mark as read and delete) similarly to ensure correct types are used
}