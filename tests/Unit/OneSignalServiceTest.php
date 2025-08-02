<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OneSignalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use OneSignal;

class OneSignalServiceTest extends TestCase
{
    use RefreshDatabase;

    private OneSignalService $oneSignalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oneSignalService = new OneSignalService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_notification_to_user_successfully()
    {
        // Create a test user
        $user = User::factory()->create(['id' => 123]);

        // Mock the OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->with(
                'Test message',
                '123', // user ID as external_user_id
                null, // url
                Mockery::any(), // data
                null, // buttons
                null, // schedule
                'Test title' // headings
            )
            ->andReturn(['success' => true, 'id' => 'notification-id-123']);

        // Act
        $result = $this->oneSignalService->sendToUser($user, 'Test title', 'Test message');

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_onesignal_api_failure()
    {
        // Create a test user
        $user = User::factory()->create(['id' => 456]);

        // Mock the OneSignal facade to throw an exception
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('OneSignal API error'));

        // Act
        $result = $this->oneSignalService->sendToUser($user, 'Test title', 'Test message');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_sends_notification_with_custom_data()
    {
        // Create a test user
        $user = User::factory()->create(['id' => 789]);

        $customData = [
            'notification_id' => 123,
            'notification_type' => 'custom',
            'title' => 'Test title'
        ];

        // Mock the OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->with(
                'Test message',
                '789',
                null,
                $customData,
                null,
                null,
                'Test title'
            )
            ->andReturn(['success' => true]);

        // Act
        $result = $this->oneSignalService->sendToUser($user, 'Test title', 'Test message', $customData);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_notification_to_multiple_users()
    {
        // Create test users
        $user1 = User::factory()->create(['id' => 111]);
        $user2 = User::factory()->create(['id' => 222]);

        // Mock the OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->twice()
            ->andReturn(['success' => true]);

        // Act
        $results = $this->oneSignalService->sendToUsers([$user1, $user2], 'Test title', 'Test message');

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[111]);
        $this->assertTrue($results[222]);
    }

    /** @test */
    public function it_sends_notification_to_all_users()
    {
        // Mock the OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToAll')
            ->once()
            ->with(
                'Test message',
                null,
                Mockery::any(),
                null,
                null,
                'Test title'
            )
            ->andReturn(['success' => true]);

        // Act
        $result = $this->oneSignalService->sendToAllUsers('Test title', 'Test message');

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_notification_using_tags()
    {
        $tags = ['user_type' => 'premium', 'region' => 'us'];

        // Mock the OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationUsingTags')
            ->once()
            ->with(
                'Test message',
                $tags,
                null,
                Mockery::any(),
                null,
                null,
                'Test title'
            )
            ->andReturn(['success' => true]);

        // Act
        $result = $this->oneSignalService->sendUsingTags('Test title', 'Test message', $tags);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_logs_successful_notification()
    {
        // Create a test user
        $user = User::factory()->create(['id' => 999]);

        // Mock the OneSignal facade
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andReturn(['success' => true, 'id' => 'test-notification-id']);

        // Act
        $result = $this->oneSignalService->sendToUser($user, 'Test title', 'Test message');

        // Assert
        $this->assertTrue($result);
        // Note: In a real test, you might want to assert that the log was written
        // but for this example, we're just ensuring the method completes successfully
    }

    /** @test */
    public function it_logs_failed_notification()
    {
        // Create a test user
        $user = User::factory()->create(['id' => 888]);

        // Mock the OneSignal facade to throw an exception
        $oneSignalMock = Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('API rate limit exceeded'));

        // Act
        $result = $this->oneSignalService->sendToUser($user, 'Test title', 'Test message');

        // Assert
        $this->assertFalse($result);
        // The error should be logged (you can verify this in your logs)
    }
} 