<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use App\Events\CustomNotificationSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CustomNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->role_id = 1; // Admin role
        $this->admin->save();

        // Create a regular user
        $this->user = User::factory()->create();
        $this->user->role_id = 2; // User role
        $this->user->save();
    }

    public function test_admin_can_send_custom_notification()
    {
        Event::fake();
        
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->postJson('/api/admin/notifications/custom', [
            'user_id' => $this->user->id,
            'title' => 'Test Notification',
            'message' => 'This is a test custom notification'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Custom notification sent successfully',
                'user_id' => $this->user->id,
                'title' => 'Test Notification'
            ]);

        // Verify the event was dispatched
        Event::assertDispatched(CustomNotificationSent::class, function ($event) {
            return $event->user->id === $this->user->id &&
                   $event->admin->id === $this->admin->id &&
                   $event->title === 'Test Notification' &&
                   $event->message === 'This is a test custom notification';
        });
    }

    public function test_custom_notification_creates_database_record()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Trigger the event directly to test the listener
        event(new CustomNotificationSent(
            $this->user,
            $this->admin,
            'Test Title',
            'Test Message'
        ));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test Message',
            'type' => 'custom',
            'sent_by_admin_id' => $this->admin->id
        ]);
    }

    public function test_regular_user_cannot_send_custom_notifications()
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/admin/notifications/custom', [
            'user_id' => $this->user->id,
            'title' => 'Test Notification',
            'message' => 'This is a test custom notification'
        ]);

        $response->assertStatus(403);
    }
} 