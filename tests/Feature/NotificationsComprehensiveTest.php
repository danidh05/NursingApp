<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class NotificationsComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create users
        $this->user = User::factory()->create(['role_id' => 2]);
        $this->user->load('role');
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->admin->load('role');
        $this->otherUser = User::factory()->create(['role_id' => 2]);
        $this->otherUser->load('role');
    }

    public function test_user_can_view_own_notifications(): void
    {
        // Create notifications for the user
        $notifications = Notification::factory(3)->create([
            'user_id' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'notifications' => [
                         '*' => ['id', 'user_id', 'message', 'type', 'read_at', 'created_at', 'updated_at']
                     ]
                 ])
                 ->assertJsonCount(3, 'notifications');
    }

    public function test_user_cannot_view_other_user_notifications(): void
    {
        // Create notifications for other user
        Notification::factory(3)->create([
            'user_id' => $this->otherUser->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'notifications');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Notification marked as read.']);

        // Verify notification was marked as read
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);
        
        // Check that read_at is not null
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_other_user_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    public function test_user_can_delete_own_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Notification deleted successfully.']);

        // Verify notification was deleted
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id
        ]);
    }

    public function test_user_cannot_delete_other_user_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    public function test_notifications_are_ordered_by_created_at_desc(): void
    {
        // Create notifications with different timestamps
        $oldNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        $newNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        $middleNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay()
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200);
        
        $notifications = $response->json('notifications');
        $this->assertEquals($newNotification->id, $notifications[0]['id']);
        $this->assertEquals($middleNotification->id, $notifications[1]['id']);
        $this->assertEquals($oldNotification->id, $notifications[2]['id']);
    }

    public function test_mark_nonexistent_notification_as_read_fails(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/notifications/99999/read');

        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_notification_fails(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/notifications/99999');

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401);
    }

    public function test_unauthorized_user_cannot_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create();

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(401);
    }

    public function test_unauthorized_user_cannot_delete_notification(): void
    {
        $notification = Notification::factory()->create();

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(401);
    }

    public function test_notification_read_status_persistence(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        Sanctum::actingAs($this->user);

        // Mark as read
        $this->postJson("/api/notifications/{$notification->id}/read");

        // Verify it stays read
        $notification->refresh();
        $this->assertNotNull($notification->read_at);

        // Fetch notifications again
        $response = $this->getJson('/api/notifications');
        
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications[0]['read_at']);
    }

    public function test_notification_deletion_is_permanent(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        // Delete notification
        $this->deleteJson("/api/notifications/{$notification->id}");

        // Verify it's completely gone
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id
        ]);

        // Try to access it again - should get 405 Method Not Allowed since GET /notifications/{id}/read doesn't exist
        $response = $this->getJson("/api/notifications/{$notification->id}/read");
        $response->assertStatus(405);
    }

    public function test_notifications_include_all_required_fields(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'message' => 'Test Message',
            'type' => 'test',
            'read_at' => null
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'notifications' => [
                         '*' => [
                             'id',
                             'user_id',
                             'message',
                             'type',
                             'read_at',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);

        $notifications = $response->json('notifications');
        $this->assertEquals('Test Message', $notifications[0]['message']);
        $this->assertEquals('test', $notifications[0]['type']);
        $this->assertNull($notifications[0]['read_at']);
    }

    public function test_empty_notifications_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'notifications');
    }

    public function test_notification_timestamps_are_preserved(): void
    {
        $createdAt = now()->subHour();
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/notifications');

        $notifications = $response->json('notifications');
        // Database may truncate microseconds and may use a different timezone, so allow a small difference
        $this->assertLessThan(120, \Carbon\Carbon::parse($notifications[0]['created_at'])->diffInSeconds($createdAt));
        $this->assertLessThan(120, \Carbon\Carbon::parse($notifications[0]['updated_at'])->diffInSeconds($createdAt));
    }
} 