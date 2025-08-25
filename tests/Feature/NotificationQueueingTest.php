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
use App\Listeners\SendUserRequestedNotification;
use App\Listeners\SendAdminUpdatedNotification;
use App\Listeners\SendCustomNotification;
use App\Listeners\SendBirthdayNotification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationQueueingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
        
        // Mock OneSignal service to prevent external API calls
        $this->mock(\App\Services\OneSignalService::class, function ($mock) {
            $mock->shouldReceive('sendToUser')->andReturn(true);
        });
    }

    public function test_user_requested_notification_listener_is_queued()
    {
        Queue::fake();

        $user = User::factory()->create();
        $request = Request::factory()->create(['user_id' => $user->id]);
        $service = Service::factory()->create();
        $request->services()->attach($service->id);

        event(new UserRequestedService($request, $user));

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) {
            return $job->class === SendUserRequestedNotification::class;
        });
    }

    public function test_admin_updated_notification_listener_is_queued()
    {
        Queue::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role_id' => 1]);
        $request = Request::factory()->create(['user_id' => $user->id]);

        event(new AdminUpdatedRequest($request, $admin, 'approved'));

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) {
            return $job->class === SendAdminUpdatedNotification::class;
        });
    }

    public function test_custom_notification_listener_is_queued()
    {
        Queue::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role_id' => 1]);

        event(new CustomNotificationSent($user, $admin, 'Test Title', 'Test Message'));

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) {
            return $job->class === SendCustomNotification::class;
        });
    }

    public function test_birthday_notification_listener_is_queued()
    {
        Queue::fake();

        $user = User::factory()->create();

        event(new UserBirthday($user));

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) {
            return $job->class === SendBirthdayNotification::class;
        });
    }

    public function test_queued_listeners_have_after_commit_setting()
    {
        $userRequestedListener = new SendUserRequestedNotification(app(\App\Services\NotificationService::class));
        $adminUpdatedListener = new SendAdminUpdatedNotification(app(\App\Services\NotificationService::class));
        $customNotificationListener = new SendCustomNotification(app(\App\Services\NotificationService::class));
        $birthdayListener = new SendBirthdayNotification(app(\App\Services\NotificationService::class));

        $this->assertTrue($userRequestedListener->afterCommit);
        $this->assertTrue($adminUpdatedListener->afterCommit);
        $this->assertTrue($customNotificationListener->afterCommit);
        $this->assertTrue($birthdayListener->afterCommit);
    }

    public function test_queued_listeners_have_retry_configuration()
    {
        $userRequestedListener = new SendUserRequestedNotification(app(\App\Services\NotificationService::class));
        $adminUpdatedListener = new SendAdminUpdatedNotification(app(\App\Services\NotificationService::class));
        $customNotificationListener = new SendCustomNotification(app(\App\Services\NotificationService::class));
        $birthdayListener = new SendBirthdayNotification(app(\App\Services\NotificationService::class));

        $this->assertEquals(3, $userRequestedListener->tries);
        $this->assertEquals(3, $adminUpdatedListener->tries);
        $this->assertEquals(3, $customNotificationListener->tries);
        $this->assertEquals(3, $birthdayListener->tries);

        $this->assertEquals([10, 60], $userRequestedListener->backoff());
        $this->assertEquals([10, 60], $adminUpdatedListener->backoff());
        $this->assertEquals([10, 60], $customNotificationListener->backoff());
        $this->assertEquals([10, 60], $birthdayListener->backoff());
    }

    public function test_queued_listeners_use_default_queue()
    {
        $userRequestedListener = new SendUserRequestedNotification(app(\App\Services\NotificationService::class));
        $adminUpdatedListener = new SendAdminUpdatedNotification(app(\App\Services\NotificationService::class));
        $customNotificationListener = new SendCustomNotification(app(\App\Services\NotificationService::class));
        $birthdayListener = new SendBirthdayNotification(app(\App\Services\NotificationService::class));

        // Should not have a custom queue property set
        $this->assertFalse(property_exists($userRequestedListener, 'queue'));
        $this->assertFalse(property_exists($adminUpdatedListener, 'queue'));
        $this->assertFalse(property_exists($customNotificationListener, 'queue'));
        $this->assertFalse(property_exists($birthdayListener, 'queue'));
    }

    public function test_queued_listeners_implement_should_queue()
    {
        $userRequestedListener = new SendUserRequestedNotification(app(\App\Services\NotificationService::class));
        $adminUpdatedListener = new SendAdminUpdatedNotification(app(\App\Services\NotificationService::class));
        $customNotificationListener = new SendCustomNotification(app(\App\Services\NotificationService::class));
        $birthdayListener = new SendBirthdayNotification(app(\App\Services\NotificationService::class));

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $userRequestedListener);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $adminUpdatedListener);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $customNotificationListener);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $birthdayListener);
    }

}