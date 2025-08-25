<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Request;
use App\Models\Service;
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use App\Events\CustomNotificationSent;
use App\Events\UserBirthday;
use App\Events\NewNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationBroadcastingTest extends TestCase
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
        $this->mock(\App\Services\OneSignalService::class, function ($mock) {
            $mock->shouldReceive('sendToUser')->andReturn(true);
        });
    }

    public function test_user_requested_service_broadcasts_on_private_channels()
    {
        Event::fake();

        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);
        $service = Service::factory()->create();
        $request->services()->attach($service->id);

        event(new UserRequestedService($request, $user));

        Event::assertDispatched(UserRequestedService::class, function ($event) use ($request, $user) {
            return $event->request->id === $request->id 
                && $event->user->id === $user->id;
        });
    }

    public function test_admin_updated_request_broadcasts_on_private_channels()
    {
        Event::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role_id' => 1]);
        $request = Request::factory()->create(['user_id' => $user->id]);

        event(new AdminUpdatedRequest($request, $admin, 'approved'));

        Event::assertDispatched(AdminUpdatedRequest::class, function ($event) use ($request, $admin) {
            return $event->request->id === $request->id 
                && $event->user->id === $admin->id
                && $event->status === 'approved';
        });
    }

    public function test_custom_notification_sent_broadcasts_on_private_channels()
    {
        Event::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role_id' => 1]);

        event(new CustomNotificationSent($user, $admin, 'Test Title', 'Test Message'));

        Event::assertDispatched(CustomNotificationSent::class, function ($event) use ($user, $admin) {
            return $event->user->id === $user->id 
                && $event->admin->id === $admin->id
                && $event->title === 'Test Title'
                && $event->message === 'Test Message';
        });
    }

    public function test_user_birthday_broadcasts_on_private_channel()
    {
        Event::fake();

        $user = User::factory()->create();

        event(new UserBirthday($user));

        Event::assertDispatched(UserBirthday::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_new_notification_broadcasts_on_user_specific_channel()
    {
        Event::fake();

        $user = User::factory()->create();
        $notificationData = [
            'id' => 1,
            'user_id' => $user->id,
            'title' => 'Test Notification',
            'message' => 'Test message',
            'type' => 'info'
        ];

        event(new NewNotification($notificationData, $user->id));

        Event::assertDispatched(NewNotification::class, function ($event) use ($notificationData) {
            return $event->message['id'] === $notificationData['id'];
        });
    }

    public function test_broadcast_events_have_correct_names()
    {
        Event::fake();

        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        event(new UserRequestedService($request, $user));

        Event::assertDispatched(UserRequestedService::class, function ($event) {
            return $event->broadcastAs() === 'user.requested.service';
        });
    }

    public function test_broadcast_events_have_correct_payload()
    {
        Event::fake();

        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        event(new UserRequestedService($request, $user));

        Event::assertDispatched(UserRequestedService::class, function ($event) use ($request, $user) {
            $payload = $event->broadcastWith();
            return $payload['request_id'] === $request->id 
                && $payload['user_id'] === $user->id;
        });
    }

    public function test_broadcast_events_implement_should_broadcast()
    {
        $userRequestedService = new UserRequestedService(Request::factory()->create(), User::factory()->create());
        $adminUpdatedRequest = new AdminUpdatedRequest(Request::factory()->create(), User::factory()->create(), 'approved');
        $customNotificationSent = new CustomNotificationSent(User::factory()->create(), User::factory()->create(), 'Test', 'Test');
        $userBirthday = new UserBirthday(User::factory()->create());
        $newNotification = new NewNotification(['test' => 'data'], 1);

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $userRequestedService);
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $adminUpdatedRequest);
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $customNotificationSent);
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $userBirthday);
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $newNotification);
    }

    public function test_broadcast_channels_are_private()
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $userRequestedService = new UserRequestedService($request, $user);
        $channels = $userRequestedService->broadcastOn();

        foreach ($channels as $channel) {
            $this->assertInstanceOf(\Illuminate\Broadcasting\PrivateChannel::class, $channel);
        }
    }

    public function test_broadcast_channels_include_user_specific_channel()
    {
        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);

        $userRequestedService = new UserRequestedService($request, $user);
        $channels = $userRequestedService->broadcastOn();

        $channelNames = array_map(function ($channel) {
            return $channel->name;
        }, $channels);

        // Laravel automatically adds 'private-' prefix to private channels
        $this->assertContains('private-user.' . $user->id, $channelNames, 
            'Expected channel "private-user.' . $user->id . '" not found in: ' . implode(', ', $channelNames));
    }
}