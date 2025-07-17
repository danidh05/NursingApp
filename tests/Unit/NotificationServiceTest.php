<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase; // This ensures a fresh database for each test

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed the roles table before running tests
        $this->artisan('db:seed', ['--class' => RoleSeeder::class]);
    }

    /** @test */
    public function it_sends_a_notification_successfully()
    {
        // Create a test user
        $user = User::factory()->create();

        // Instantiate the NotificationService
        $notificationService = new NotificationService();

        // Act: Create notification via the service
        $notification = $notificationService->createNotification($user, 'Test Title', 'Test Body', 'info');

        // Assert: Verify the notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'message' => 'Test Body',
            'type' => 'info',
        ]);

        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('Test Body', $notification->message);
    }

    /** @test */
    public function it_handles_failed_notification_sending()
    {
        // Create a test user
        $user = User::factory()->create();

        // Instantiate the NotificationService
        $notificationService = new NotificationService();

        // Act: Create notification via the service
        $notification = $notificationService->createNotification($user, 'Test Title', 'Test Body', 'error');

        // Assert: Verify the notification was created with error type
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'message' => 'Test Body',
            'type' => 'error',
        ]);

        $this->assertEquals('error', $notification->type);
    }
}