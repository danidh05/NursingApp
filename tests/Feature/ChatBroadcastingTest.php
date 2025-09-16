<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\Request as ServiceRequest;
use App\Events\Chat\MessageCreated;
use App\Events\Chat\ThreadClosed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

class ChatBroadcastingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable chat feature
        Config::set('chat.enabled', true);
        
        // Set up broadcasting for testing
        Config::set('broadcasting.default', 'reverb');
        Config::set('broadcasting.connections.reverb.driver', 'reverb');
        Config::set('broadcasting.connections.reverb.key', 'test-key');
        Config::set('broadcasting.connections.reverb.secret', 'test-secret');
        Config::set('broadcasting.connections.reverb.app_id', 'test-app-id');
        Config::set('broadcasting.connections.reverb.options.host', 'localhost');
        Config::set('broadcasting.connections.reverb.options.port', 8080);
        Config::set('broadcasting.connections.reverb.options.scheme', 'http');
        Config::set('broadcasting.connections.reverb.options.useTLS', false);
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
    }

    public function test_message_created_broadcasts_to_private_channel()
    {
        // Fake events to test broadcasting
        Event::fake();
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Post message and verify it broadcasts
        $response = $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Test message'
        ]);
        
        $response->assertStatus(200);
        
        // Verify message was created
        $this->assertDatabaseHas('chat_messages', [
            'thread_id' => $thread->id,
            'text' => 'Test message',
            'type' => 'text'
        ]);
        
        // Verify the MessageCreated event was dispatched
        Event::assertDispatched(MessageCreated::class, function ($event) use ($thread) {
            return $event->threadId === $thread->id;
        });
    }

    public function test_thread_closed_broadcasts_to_private_channel()
    {
        Event::fake();
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Close thread and verify it broadcasts
        $response = $this->patchJson("/api/chat/threads/{$thread->id}/close");
        
        $response->assertStatus(200);
        
        // Verify thread was closed
        $this->assertDatabaseHas('chat_threads', [
            'id' => $thread->id,
            'status' => 'closed'
        ]);
        
        // Verify the ThreadClosed event was dispatched
        Event::assertDispatched(ThreadClosed::class, function ($event) use ($thread) {
            return $event->threadId === $thread->id;
        });
    }

    public function test_message_created_event_has_correct_broadcast_data()
    {
        Event::fake();
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Post message
        $response = $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Test message'
        ]);
        
        $response->assertStatus(200);
        
        // Verify event was dispatched with correct data
        Event::assertDispatched(MessageCreated::class, function ($event) use ($thread) {
            return $event->threadId === $thread->id 
                && isset($event->payload['id'])
                && isset($event->payload['text'])
                && $event->payload['text'] === 'Test message';
        });
    }

    public function test_thread_closed_event_has_correct_broadcast_data()
    {
        Event::fake();
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Close thread
        $response = $this->patchJson("/api/chat/threads/{$thread->id}/close");
        
        $response->assertStatus(200);
        
        // Verify event was dispatched with correct data
        Event::assertDispatched(ThreadClosed::class, function ($event) use ($thread) {
            return $event->threadId === $thread->id;
        });
    }

    public function test_broadcasting_events_have_correct_channel_names()
    {
        Event::fake();

        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        // Post message
        $response = $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Test message'
        ]);

        $response->assertStatus(200);

        // Verify MessageCreated event was dispatched
        Event::assertDispatched(MessageCreated::class);
    }

    public function test_broadcasting_events_have_correct_channel_names_for_thread_closed()
    {
        Event::fake();

        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        // Close thread
        $response = $this->patchJson("/api/chat/threads/{$thread->id}/close");

        $response->assertStatus(200);

        // Verify ThreadClosed event was dispatched
        Event::assertDispatched(ThreadClosed::class);
    }

    public function test_broadcasting_events_have_correct_event_names()
    {
        Event::fake();
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Post message
        $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Test message'
        ]);
        
        // Verify MessageCreated event has correct name
        Event::assertDispatched(MessageCreated::class, function ($event) {
            return $event->broadcastAs() === 'message.created';
        });
        
        // Close thread
        $this->patchJson("/api/chat/threads/{$thread->id}/close");
        
        // Verify ThreadClosed event has correct name
        Event::assertDispatched(ThreadClosed::class, function ($event) {
            return $event->broadcastAs() === 'thread.closed';
        });
    }

    public function test_broadcasting_events_respect_chat_enabled_config()
    {
        Event::fake();
        
        // Disable chat feature
        Config::set('chat.enabled', false);
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Post message
        $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Test message'
        ]);
        
        // Verify MessageCreated event was not dispatched when chat is disabled
        Event::assertNotDispatched(MessageCreated::class);
        
        // Close thread
        $this->patchJson("/api/chat/threads/{$thread->id}/close");
        
        // Verify ThreadClosed event was not dispatched when chat is disabled
        Event::assertNotDispatched(ThreadClosed::class);
    }

    public function test_broadcasting_events_work_with_different_message_types()
    {
        Event::fake();
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($user, ['*']);
        
        // Test text message
        $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Text message'
        ]);
        
        // Test image message
        $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'image',
            'mediaPath' => "chats/{$thread->id}/test.jpg"
        ]);
        
        // Test location message
        $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'location',
            'lat' => 34.0522,
            'lng' => -118.2437
        ]);
        
        // Verify all message types triggered broadcasting
        Event::assertDispatched(MessageCreated::class, 3);
    }

    public function test_broadcasting_events_work_with_admin_users()
    {
        Event::fake();
        
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'admin_id' => $admin->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        Sanctum::actingAs($admin, ['*']);
        
        // Admin posts message
        $this->postJson("/api/chat/threads/{$thread->id}/messages", [
            'type' => 'text',
            'text' => 'Admin message'
        ]);
        
        // Verify MessageCreated event was dispatched
        Event::assertDispatched(MessageCreated::class, function ($event) use ($thread) {
            return $event->threadId === $thread->id;
        });
        
        // Admin closes thread
        $this->patchJson("/api/chat/threads/{$thread->id}/close");
        
        // Verify ThreadClosed event was dispatched
        Event::assertDispatched(ThreadClosed::class, function ($event) use ($thread) {
            return $event->threadId === $thread->id;
        });
    }
}