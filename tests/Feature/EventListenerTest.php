<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use App\Models\User;
use App\Models\Request; // Make sure to import the Request model

use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;

class EventListenerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']); // Seed roles if necessary
    }

    /** @test */
    public function it_sends_notification_when_user_requests_service()
    {
        // Create a user
        $user = User::factory()->create([
            'phone_number' => '1-320-734-6160', // Include any other required fields
        ]);

        // Create a nurse (if needed for the request)
        $nurse = \App\Models\Nurse::factory()->create();

        // Create a request
        $request = Request::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id, // Make sure the nurse ID is valid
            'full_name' => 'John Doe',
            // Other fields as necessary
        ]);

        // Prepare the NotificationService mock
        $notificationServiceMock = \Mockery::mock(NotificationService::class);
        $notificationServiceMock->shouldReceive('createNotification')
            ->once()
            ->with(
                \Mockery::any(), // User object
                'Service Request Submitted',
                'Your service request has been submitted successfully.',
                'success'
            );

        // Bind the NotificationService mock to the service container
        $this->app->instance(NotificationService::class, $notificationServiceMock);

        // Dispatch the event
        event(new UserRequestedService($request, $user));

        // You can add assertions to verify that the notification was sent
        // This is already handled by the mock's expectations
    }



    /** @test */
    public function it_sends_notification_when_admin_updates_request()
    {
        // Create a user and a nurse request
        $user = User::factory()->create();
        
        $nurseRequest = Request::factory()->create([
            'user_id' => $user->id, // Associate the request with the user
            'status' => 'pending', // Initial status
        ]);

        // Mock the NotificationService
        $notificationServiceMock = \Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $notificationServiceMock);

        // Expect the createNotification method to be called
        $notificationServiceMock->shouldReceive('createNotification')
            ->once()
            ->with(\Mockery::any(), 'Request Status Updated', 'Your request status has been updated to: approved', 'info');

        // Simulate the event
        event(new AdminUpdatedRequest($nurseRequest, $user, 'approved'));
    }
}