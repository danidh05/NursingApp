<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use App\Models\User;
use App\Models\Request;
use App\Models\Notification;
use App\Services\OneSignalService;
use App\Listeners\SendUserRequestedNotification;
use App\Listeners\SendAdminUpdatedNotification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Events\CallQueuedListener;

class EventListenerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set queue connection to sync for immediate execution
        config(['queue.default' => 'sync']);
        
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
        
        // Mock OneSignal service to prevent external API calls
        $this->mock(OneSignalService::class, function ($mock) {
            $mock->shouldReceive('sendToUser')->andReturn(true);
        });
    }

    /** @test */
    public function it_sends_notification_when_user_requests_service()
    {
        // Create a user with an area
        $testArea = \App\Models\Area::first();
        $user = User::factory()->create([
            'phone_number' => '1-320-734-6160',
            'area_id' => $testArea->id,
        ]);

        // Create a nurse (if needed for the request)
        $nurse = \App\Models\Nurse::factory()->create();

        // Create services and service area pricing
        $services = \App\Models\Service::factory(2)->create();
        foreach ($services as $service) {
            \App\Models\ServiceAreaPrice::create([
                'service_id' => $service->id,
                'area_id' => $testArea->id,
                'price' => 100.00,
            ]);
        }

        // Create a request
        $request = Request::factory()->create([
            'user_id' => $user->id,
            'nurse_id' => $nurse->id,
            'area_id' => $testArea->id,
            'full_name' => 'John Doe',
            'status' => Request::STATUS_SUBMITTED,
        ]);

        // Attach services to the request
        $request->services()->attach($services->pluck('id')->toArray());

        // Dispatch the event
        event(new UserRequestedService($request, $user));

        // Verify that the notification was actually created in the database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Service Request Submitted',
            'message' => 'Your service request has been submitted successfully.',
            'type' => 'success'
        ]);
    }

    /** @test */
    public function it_sends_notification_when_admin_updates_request()
    {
        // Create a user and a nurse request
        $testArea = \App\Models\Area::first();
        $user = User::factory()->create(['area_id' => $testArea->id]);
        
        $nurseRequest = Request::factory()->create([
            'user_id' => $user->id,
            'area_id' => $testArea->id,
            'status' => Request::STATUS_SUBMITTED,
        ]);

        // Dispatch the event
        event(new AdminUpdatedRequest($nurseRequest, $user, 'approved'));

        // Verify that the notification was actually created in the database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Request Status Updated',
            'message' => 'Your service request status has been updated to: approved',
            'type' => 'info'
        ]);
    }

    /** @test */
    public function listener_can_be_called_directly()
    {
        // Create a user with an area
        $testArea = \App\Models\Area::first();
        $user = User::factory()->create([
            'phone_number' => '1-320-734-6160',
            'area_id' => $testArea->id,
        ]);

        // Create a request
        $request = Request::factory()->create([
            'user_id' => $user->id,
            'area_id' => $testArea->id,
            'status' => Request::STATUS_SUBMITTED,
        ]);

        // Create the event
        $event = new UserRequestedService($request, $user);

        // Create and call the listener directly
        $listener = new SendUserRequestedNotification(app(\App\Services\NotificationService::class));
        $listener->handle($event);

        // Verify that the notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Service Request Submitted',
            'message' => 'Your service request has been submitted successfully.',
            'type' => 'success'
        ]);
    }
}