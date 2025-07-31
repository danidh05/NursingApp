<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Services\OneSignalService;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Mockery;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase; // This ensures a fresh database for each test

    private $oneSignalServiceMock;
    private $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed the roles table before running tests
        $this->artisan('db:seed', ['--class' => RoleSeeder::class]);

        // Create a mock for OneSignalService
        $this->oneSignalServiceMock = Mockery::mock(OneSignalService::class);
        
        // Instantiate NotificationService with the mocked dependency
        $this->notificationService = new NotificationService($this->oneSignalServiceMock);
    }

    /** @test */
    public function it_sends_a_notification_successfully()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock OneSignal service to return true (successful send)
        $this->oneSignalServiceMock
            ->shouldReceive('sendToUser')
            ->once()
            ->with($user, 'Test Title', 'Test Body', Mockery::any())
            ->andReturn(true);

        // Act: Create notification via the service
        $notification = $this->notificationService->createNotification($user, 'Test Title', 'Test Body', 'info');

        // Assert: Verify the notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'message' => 'Test Body',
            'type' => 'info',
        ]);

        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('Test Title', $notification->title);
        $this->assertEquals('Test Body', $notification->message);
    }

    /** @test */
    public function it_handles_failed_notification_sending()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock OneSignal service to return false (failed send)
        $this->oneSignalServiceMock
            ->shouldReceive('sendToUser')
            ->once()
            ->with($user, 'Test Title', 'Test Body', Mockery::any())
            ->andReturn(false);

        // Act: Create notification via the service
        $notification = $this->notificationService->createNotification($user, 'Test Title', 'Test Body', 'error');

        // Assert: Verify the notification was created with error type even if push failed
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'message' => 'Test Body',
            'type' => 'error',
        ]);

        $this->assertEquals('error', $notification->type);
    }

    /** @test */
    public function it_creates_notification_without_device_token()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock should be called since we're using user_id now
        $this->oneSignalServiceMock
            ->shouldReceive('sendToUser')
            ->once()
            ->andReturn(true);

        // Act: Create notification via the service
        $notification = $this->notificationService->createNotification($user, 'Test Title', 'Test Body', 'info');

        // Assert: Verify the notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'message' => 'Test Body',
            'type' => 'info',
        ]);
    }

    /** @test */
    public function it_sends_birthday_notification()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock OneSignal service
        $this->oneSignalServiceMock
            ->shouldReceive('sendToUser')
            ->once()
            ->with($user, '🎉 Happy Birthday!', 'Wishing you a wonderful birthday filled with joy and happiness!', Mockery::any())
            ->andReturn(true);

        // Act: Send birthday notification
        $notification = $this->notificationService->sendBirthdayNotification($user);

        // Assert: Verify the birthday notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => '🎉 Happy Birthday!',
            'message' => 'Wishing you a wonderful birthday filled with joy and happiness!',
            'type' => 'birthday',
        ]);

        $this->assertEquals('birthday', $notification->type);
    }
}