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
        // Arrange: Mock the HTTP request to OneSignal API
        Http::fake([ 
            'https://api.onesignal.com/notifications' => Http::response([
                'success' => true,
            ], 200), // Mock a successful response
        ]);

        // Create a test user
        $user = User::factory()->create();

        // Instantiate the NotificationService
        $notificationService = new NotificationService();

        // Act: Send notification via the service
        $response = $notificationService->sendNotification([$user->id], 'Test Title', 'Test Body');

        // Assert: Verify the request payload and successful notification
        Http::assertSent(function ($request) use ($user) {
            return $request->url() == 'https://api.onesignal.com/notifications'
                && $request['include_external_user_ids'] == [$user->id]
                && $request['headings']['en'] == 'Test Title'
                && $request['contents']['en'] == 'Test Body';
        });

        $this->assertEquals("Notification sent successfully.", $response);
    }

    /** @test */
    public function it_handles_failed_notification_sending()
    {
        // Arrange: Mock the HTTP request to simulate failure
        Http::fake([ 
            'https://api.onesignal.com/notifications' => Http::response([
                'error' => 'Unauthorized',
            ], 401), // Mock a failed response
        ]);

        // Create a test user
        $user = User::factory()->create();

        // Instantiate the NotificationService
        $notificationService = new NotificationService();

        // Act: Attempt to send notification
        $response = $notificationService->sendNotification([$user->id], 'Test Title', 'Test Body');

        // Assert: Check that the failure response is handled correctly
        $this->assertEquals("Failed to send notification: {\"error\":\"Unauthorized\"}", $response);
    }
}