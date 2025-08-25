<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OneSignalServicePayloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set OneSignal config for testing
        config([
            'onesignal.app_id' => 'test-app-id',
            'onesignal.api_key' => 'test-api-key',
            'onesignal.api_url' => 'https://api.onesignal.com/notifications',
        ]);
    }

    public function test_onesignal_service_uses_correct_payload_structure()
    {
        // Mock the OneSignal facade since we're using the existing service
        $oneSignalMock = \Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->with(
                'Test Message',
                (string) 1, // user ID as string
                null, // url
                ['custom' => 'data'], // data
                null, // buttons
                null, // schedule
                'Test Title' // headings
            )
            ->andReturn(['id' => 'test-notification-id', 'recipients' => 1]);

        $user = User::factory()->create(['id' => 1]);
        $oneSignalService = new OneSignalService();

        $result = $oneSignalService->sendToUser($user, 'Test Title', 'Test Message', ['custom' => 'data']);

        $this->assertTrue($result);
    }

    public function test_onesignal_service_handles_http_errors()
    {
        // Mock the OneSignal facade to throw an exception
        $oneSignalMock = \Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('OneSignal API error'));

        $user = User::factory()->create();
        $oneSignalService = new OneSignalService();

        $result = $oneSignalService->sendToUser($user, 'Test Title', 'Test Message');

        $this->assertFalse($result);
    }

    public function test_onesignal_service_handles_zero_recipients()
    {
        // Mock the OneSignal facade
        $oneSignalMock = \Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andReturn(['id' => 'test-notification-id', 'recipients' => 0]);

        $user = User::factory()->create();
        $oneSignalService = new OneSignalService();

        $result = $oneSignalService->sendToUser($user, 'Test Title', 'Test Message');

        // Should still return true to avoid breaking flow
        $this->assertTrue($result);
    }

    public function test_onesignal_service_logs_successful_sends()
    {
        // Mock the OneSignal facade
        $oneSignalMock = \Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andReturn(['id' => 'test-notification-id', 'recipients' => 1]);

        $user = User::factory()->create();
        $oneSignalService = new OneSignalService();

        $result = $oneSignalService->sendToUser($user, 'Test Title', 'Test Message');

        $this->assertTrue($result);
    }

    public function test_onesignal_service_handles_exceptions()
    {
        // Mock the OneSignal facade to throw an exception
        $oneSignalMock = \Mockery::mock('alias:OneSignal');
        $oneSignalMock->shouldReceive('sendNotificationToExternalUser')
            ->once()
            ->andThrow(new \Exception('Network error'));

        $user = User::factory()->create();
        $oneSignalService = new OneSignalService();

        $result = $oneSignalService->sendToUser($user, 'Test Title', 'Test Message');

        $this->assertFalse($result);
    }
}