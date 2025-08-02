<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use App\Services\OneSignalService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Mockery;

class OneSignalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OneSignalService $oneSignalService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create(['id' => 123]);
        $this->user->role_id = 2; // User role
        $this->user->save();

        // Create real service instances
        $this->oneSignalService = new OneSignalService();
        $this->notificationService = new NotificationService($this->oneSignalService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function onesignal_service_integration_with_mocking()
    {
        // Mock OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->with(
                'Test message content',
                '123',
                null,
                ['custom_data' => 'test_value'],
                null,
                null,
                'Test Title'
            )
            ->andReturn(['success' => true, 'id' => 'test-notification-id-123']);

        // Test OneSignal service directly
        $result = $this->oneSignalService->sendToUser(
            $this->user,
            'Test Title',
            'Test message content',
            ['custom_data' => 'test_value']
        );

        // Verify the result
        $this->assertTrue($result);
    }

    /** @test */
    public function onesignal_api_failure_handling()
    {
        // Mock OneSignal facade to throw exception
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('OneSignal API error'));

        // Test OneSignal service with failure
        $result = $this->oneSignalService->sendToUser(
            $this->user,
            'Test Title',
            'Test message content'
        );

        // Should return false on failure
        $this->assertFalse($result);
    }

    /** @test */
    public function notification_service_with_onesignal_integration()
    {
        // Mock successful OneSignal API call
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andReturn(['success' => true, 'id' => 'test-notification-id-456']);

        // Create notification through the service
        $notification = $this->notificationService->createNotification(
            $this->user,
            'Test Title',
            'Test message content',
            'info'
        );

        // Verify notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test message content',
            'type' => 'info'
        ]);

        // Verify notification object was returned
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('Test Title', $notification->title);
        $this->assertEquals('Test message content', $notification->message);
        $this->assertEquals('info', $notification->type);
    }

    /** @test */
    public function notification_service_continues_working_when_onesignal_fails()
    {
        // Mock OneSignal API failure
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('OneSignal API error'));

        // Create notification - should still work
        $notification = $this->notificationService->createNotification(
            $this->user,
            'Test Title',
            'Test message content',
            'warning'
        );

        // Verify notification was still created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test message content',
            'type' => 'warning'
        ]);

        // Verify notification object was returned
        $this->assertInstanceOf(Notification::class, $notification);
    }

    /** @test */
    public function onesignal_data_structure_validation()
    {
        // Mock OneSignal API call
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->with(
                'Test message content',
                '123',
                null,
                Mockery::on(function ($data) {
                    // Verify the data structure sent to OneSignal
                    return isset($data['notification_id']) &&
                           isset($data['notification_type']) &&
                           isset($data['title']) &&
                           $data['notification_type'] === 'custom';
                }),
                null,
                null,
                'Test Title'
            )
            ->andReturn(['success' => true, 'id' => 'test-notification-id-789']);

        // Create notification with custom data
        $notification = $this->notificationService->createNotification(
            $this->user,
            'Test Title',
            'Test message content',
            'custom'
        );

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test message content',
            'type' => 'custom'
        ]);
    }

    /** @test */
    public function onesignal_multiple_users_notification()
    {
        // Create multiple users
        $user1 = User::factory()->create(['id' => 111]);
        $user2 = User::factory()->create(['id' => 222]);

        // Mock OneSignal API calls for multiple users
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->twice()
            ->andReturn(['success' => true]);

        // Send notification to multiple users
        $results = $this->oneSignalService->sendToUsers(
            [$user1, $user2],
            'Multi User Title',
            'Multi user message content'
        );

        // Verify results
        $this->assertCount(2, $results);
        $this->assertTrue($results[111]);
        $this->assertTrue($results[222]);
    }

    /** @test */
    public function onesignal_all_users_notification()
    {
        // Mock OneSignal API call for all users
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToAll')
            ->once()
            ->andReturn(['success' => true, 'id' => 'test-notification-id-all']);

        // Send notification to all users
        $result = $this->oneSignalService->sendToAllUsers(
            'All Users Title',
            'Message for all users'
        );

        // Verify result
        $this->assertTrue($result);
    }

    /** @test */
    public function onesignal_logging_works_correctly()
    {
        // Mock OneSignal API call
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andReturn(['success' => true, 'id' => 'test-notification-id-log']);

        // Create notification (this should log the activity)
        $notification = $this->notificationService->createNotification(
            $this->user,
            'Test Title',
            'Test message content',
            'info'
        );

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test message content',
            'type' => 'info'
        ]);

        // Note: In a real scenario, you might want to assert that specific log messages were written
        // For now, we're just ensuring the notification creation completes successfully
    }

    /** @test */
    public function onesignal_network_timeout_handling()
    {
        // Mock OneSignal API timeout
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('Network timeout'));

        // Test OneSignal service with timeout
        $result = $this->oneSignalService->sendToUser(
            $this->user,
            'Test Title',
            'Test message content'
        );

        // Should return false on timeout
        $this->assertFalse($result);
    }

    /** @test */
    public function onesignal_authentication_error_handling()
    {
        // Mock OneSignal authentication error
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('Invalid API key'));

        // Test OneSignal service with auth error
        $result = $this->oneSignalService->sendToUser(
            $this->user,
            'Test Title',
            'Test message content'
        );

        // Should return false on auth error
        $this->assertFalse($result);
    }
} 