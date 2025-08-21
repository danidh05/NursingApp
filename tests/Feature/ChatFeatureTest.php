<?php

namespace Tests\Feature;

use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $this->app['config']->set('chat.enabled', true);
        $this->app['config']->set('chat.signed_url_ttl', 900);
        $this->app['config']->set('chat.redact_messages', true);
        
        Queue::fake();
        Event::fake();
    }

    public function test_complete_chat_flow()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create([
            'user_id' => $user->id,
            'status' => ServiceRequest::STATUS_SUBMITTED,
        ]);

        Sanctum::actingAs($user, ['*']);

        // 1. Open chat thread
        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open->assertStatus(200);
        $threadId = $open->json('data.threadId');
        $this->assertNotEmpty($threadId);

        // 2. Post text message
        $textMsg = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => 'Hello Admin',
        ]);
        $textMsg->assertStatus(200);
        $this->assertNotEmpty($textMsg->json('data.id'));

        // 3. Post image message
        $imageMsg = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'image',
            'mediaPath' => 'chats/'.$threadId.'/test_image.jpg',
        ]);
        $imageMsg->assertStatus(200);
        $this->assertNotEmpty($imageMsg->json('data.id'));

        // 4. Post location message
        $locationMsg = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'location',
            'lat' => 34.0522,
            'lng' => -118.2437,
        ]);
        $locationMsg->assertStatus(200);
        $this->assertNotEmpty($locationMsg->json('data.id'));

        // 5. List messages
        $list = $this->getJson('/api/chat/threads/'.$threadId.'/messages');
        $list->assertStatus(200);
        $messages = $list->json('data.messages');
        $this->assertCount(3, $messages);
        
        // Verify message order (newest first - ID DESC)
        $this->assertEquals('location', $messages[0]['type']);
        $this->assertEquals(34.0522, $messages[0]['lat']);
        $this->assertEquals('image', $messages[1]['type']);
        $this->assertEquals('text', $messages[2]['type']);
        $this->assertEquals('Hello Admin', $messages[2]['text']);

        // 6. Close thread
        $close = $this->patchJson('/api/chat/threads/'.$threadId.'/close');
        $close->assertStatus(200);
        $this->assertEquals('closed', $close->json('data.status'));
        $this->assertNotEmpty($close->json('data.closed_at'));
    }

    public function test_chat_thread_idempotency()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        // Open thread twice
        $open1 = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open2 = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        
        $open1->assertStatus(200);
        $open2->assertStatus(200);
        
        $this->assertEquals($open1->json('data.threadId'), $open2->json('data.threadId'));
    }

    public function test_close_thread_idempotency()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $threadId = $open->json('data.threadId');

        // Close thread twice
        $close1 = $this->patchJson('/api/chat/threads/'.$threadId.'/close');
        $close2 = $this->patchJson('/api/chat/threads/'.$threadId.'/close');
        
        $close1->assertStatus(200);
        $close2->assertStatus(200);
        $this->assertEquals('closed', $close2->json('data.status'));
    }

    public function test_cannot_post_to_closed_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $threadId = $open->json('data.threadId');

        // Close the thread
        $this->patchJson('/api/chat/threads/'.$threadId.'/close');

        // Try to post message to closed thread
        $post = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => 'Should fail',
        ]);
        
        $post->assertStatus(403);
    }

    public function test_unauthorized_access()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $otherUser = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        Sanctum::actingAs($otherUser, ['*']);

        // Other user cannot open thread for someone else's request
        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open->assertStatus(403);
    }

    public function test_invalid_message_validation()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $threadId = $open->json('data.threadId');

        // Test invalid message type
        $invalidType = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'invalid',
            'text' => 'test',
        ]);
        $invalidType->assertStatus(422);

        // Test empty text
        $emptyText = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => '',
        ]);
        $emptyText->assertStatus(422);

        // Test invalid coordinates
        $invalidCoords = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'location',
            'lat' => 'invalid',
            'lng' => 'invalid',
        ]);
        $invalidCoords->assertStatus(422);

        // Test invalid media path
        $invalidMedia = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'image',
            'mediaPath' => 'invalid/path.jpg',
        ]);
        $invalidMedia->assertStatus(422);
    }

    public function test_message_pagination()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $threadId = $open->json('data.threadId');

        // Post multiple messages
        for ($i = 1; $i <= 25; $i++) {
            $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
                'type' => 'text',
                'text' => "Message $i",
            ]);
        }

        // Test default pagination (20 messages)
        $list = $this->getJson('/api/chat/threads/'.$threadId.'/messages');
        $list->assertStatus(200);
        $messages = $list->json('data.messages');
        $this->assertCount(20, $messages);
        $this->assertNotEmpty($list->json('data.nextCursor'));

        // Test custom limit
        $customLimit = $this->getJson('/api/chat/threads/'.$threadId.'/messages?limit=10');
        $customLimit->assertStatus(200);
        $this->assertCount(10, $customLimit->json('data.messages'));

        // Test cursor pagination
        $cursor = $list->json('data.nextCursor');
        $nextPage = $this->getJson('/api/chat/threads/'.$threadId.'/messages?cursor='.$cursor);
        $nextPage->assertStatus(200);
        $this->assertCount(5, $nextPage->json('data.messages')); // Remaining 5 messages
    }

    public function test_admin_can_access_user_chat()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        Sanctum::actingAs($admin, ['*']);

        // Admin can open thread
        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open->assertStatus(200);
        $threadId = $open->json('data.threadId');

        // Admin can post message
        $post = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => 'Admin message',
        ]);
        $post->assertStatus(200);

        // Admin can list messages
        $list = $this->getJson('/api/chat/threads/'.$threadId.'/messages');
        $list->assertStatus(200);
        $this->assertCount(1, $list->json('data.messages'));

        // Admin can close thread
        $close = $this->patchJson('/api/chat/threads/'.$threadId.'/close');
        $close->assertStatus(200);
    }

    public function test_feature_disabled_returns_501()
    {
        $this->app['config']->set('chat.enabled', false);
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        // All chat endpoints should return 501 when feature is disabled
        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open->assertStatus(501);

        // Create a thread directly for testing other endpoints
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $list = $this->getJson('/api/chat/threads/'.$thread->id.'/messages');
        $list->assertStatus(501);

        $post = $this->postJson('/api/chat/threads/'.$thread->id.'/messages', [
            'type' => 'text',
            'text' => 'test',
        ]);
        $post->assertStatus(501);

        $close = $this->patchJson('/api/chat/threads/'.$thread->id.'/close');
        $close->assertStatus(501);
    }

    public function test_unauthenticated_access_denied()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        // Test without authentication
        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open->assertStatus(401);

        // Create a thread directly for testing other endpoints
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $list = $this->getJson('/api/chat/threads/'.$thread->id.'/messages');
        $list->assertStatus(401);

        $post = $this->postJson('/api/chat/threads/'.$thread->id.'/messages', [
            'type' => 'text',
            'text' => 'test',
        ]);
        $post->assertStatus(401);

        $close = $this->patchJson('/api/chat/threads/'.$thread->id.'/close');
        $close->assertStatus(401);
    }

    public function test_signed_urls_for_image_messages()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $threadId = $open->json('data.threadId');

        // Post image message
        $imageMsg = $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'image',
            'mediaPath' => 'chats/'.$threadId.'/test_image.jpg',
        ]);
        $imageMsg->assertStatus(200);

        // List messages and check for signed URL
        $list = $this->getJson('/api/chat/threads/'.$threadId.'/messages');
        $list->assertStatus(200);
        $messages = $list->json('data.messages');
        
        $imageMessage = collect($messages)->firstWhere('type', 'image');
        $this->assertNotNull($imageMessage);
        $this->assertNotEmpty($imageMessage['media_url']);
        $this->assertStringContainsString('test_image.jpg', $imageMessage['media_url']);
    }

    public function test_message_ordering()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $open = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $threadId = $open->json('data.threadId');

        // Post messages with delays to ensure different timestamps
        $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => 'First message',
        ]);

        sleep(1);

        $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => 'Second message',
        ]);

        sleep(1);

        $this->postJson('/api/chat/threads/'.$threadId.'/messages', [
            'type' => 'text',
            'text' => 'Third message',
        ]);

        // List messages and verify order (newest first - ID DESC)
        $list = $this->getJson('/api/chat/threads/'.$threadId.'/messages');
        $list->assertStatus(200);
        $messages = $list->json('data.messages');
        
        $this->assertCount(3, $messages);
        $this->assertEquals('Third message', $messages[0]['text']); // Newest first
        $this->assertEquals('Second message', $messages[1]['text']); // Middle
        $this->assertEquals('First message', $messages[2]['text']); // Oldest last
    }

    public function test_multiple_requests_have_separate_chats()
    {
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user, ['*']);

        // Create first request and its chat
        $request1 = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $open1 = $this->postJson('/api/requests/'.$request1->id.'/chat/open');
        $open1->assertStatus(200);
        $threadId1 = $open1->json('data.threadId');

        // Create second request and its chat
        $request2 = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $open2 = $this->postJson('/api/requests/'.$request2->id.'/chat/open');
        $open2->assertStatus(200);
        $threadId2 = $open2->json('data.threadId');

        // Verify they are different threads
        $this->assertNotEquals($threadId1, $threadId2, 'Different requests should have different chat threads');

        // Post messages to first chat
        $this->postJson('/api/chat/threads/'.$threadId1.'/messages', [
            'type' => 'text',
            'text' => 'Message in first chat',
        ]);

        // Post messages to second chat
        $this->postJson('/api/chat/threads/'.$threadId2.'/messages', [
            'type' => 'text',
            'text' => 'Message in second chat',
        ]);

        // Verify messages are isolated to their respective threads
        $list1 = $this->getJson('/api/chat/threads/'.$threadId1.'/messages');
        $list1->assertStatus(200);
        $messages1 = $list1->json('data.messages');
        $this->assertCount(1, $messages1);
        $this->assertEquals('Message in first chat', $messages1[0]['text']);

        $list2 = $this->getJson('/api/chat/threads/'.$threadId2.'/messages');
        $list2->assertStatus(200);
        $messages2 = $list2->json('data.messages');
        $this->assertCount(1, $messages2);
        $this->assertEquals('Message in second chat', $messages2[0]['text']);
    }

    public function test_multiple_requests_chat_isolation()
    {
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user, ['*']);

        // Create multiple requests
        $request1 = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $request2 = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $request3 = ServiceRequest::factory()->create(['user_id' => $user->id]);

        // Open chats for all requests
        $thread1 = $this->postJson('/api/requests/'.$request1->id.'/chat/open')->json('data.threadId');
        $thread2 = $this->postJson('/api/requests/'.$request2->id.'/chat/open')->json('data.threadId');
        $thread3 = $this->postJson('/api/requests/'.$request3->id.'/chat/open')->json('data.threadId');

        // Verify all threads are unique
        $this->assertNotEquals($thread1, $thread2);
        $this->assertNotEquals($thread2, $thread3);
        $this->assertNotEquals($thread1, $thread3);

        // Post different messages to each thread
        $this->postJson('/api/chat/threads/'.$thread1.'/messages', [
            'type' => 'text',
            'text' => 'Chat 1 message',
        ]);

        $this->postJson('/api/chat/threads/'.$thread2.'/messages', [
            'type' => 'text',
            'text' => 'Chat 2 message',
        ]);

        $this->postJson('/api/chat/threads/'.$thread3.'/messages', [
            'type' => 'text',
            'text' => 'Chat 3 message',
        ]);

        // Verify each thread only contains its own messages
        $messages1 = $this->getJson('/api/chat/threads/'.$thread1.'/messages')->json('data.messages');
        $messages2 = $this->getJson('/api/chat/threads/'.$thread2.'/messages')->json('data.messages');
        $messages3 = $this->getJson('/api/chat/threads/'.$thread3.'/messages')->json('data.messages');

        $this->assertCount(1, $messages1);
        $this->assertEquals('Chat 1 message', $messages1[0]['text']);

        $this->assertCount(1, $messages2);
        $this->assertEquals('Chat 2 message', $messages2[0]['text']);

        $this->assertCount(1, $messages3);
        $this->assertEquals('Chat 3 message', $messages3[0]['text']);
    }

    public function test_concurrent_request_chat_creation()
    {
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user, ['*']);

        // Create multiple requests simultaneously
        $request1 = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $request2 = ServiceRequest::factory()->create(['user_id' => $user->id]);

        // Open chats concurrently (simulating rapid requests)
        $open1 = $this->postJson('/api/requests/'.$request1->id.'/chat/open');
        $open2 = $this->postJson('/api/requests/'.$request2->id.'/chat/open');

        $open1->assertStatus(200);
        $open2->assertStatus(200);

        $threadId1 = $open1->json('data.threadId');
        $threadId2 = $open2->json('data.threadId');

        // Verify unique threads were created
        $this->assertNotEquals($threadId1, $threadId2);

        // Verify both threads are functional
        $this->postJson('/api/chat/threads/'.$threadId1.'/messages', [
            'type' => 'text',
            'text' => 'Thread 1 message',
        ])->assertStatus(200);

        $this->postJson('/api/chat/threads/'.$threadId2.'/messages', [
            'type' => 'text',
            'text' => 'Thread 2 message',
        ])->assertStatus(200);
    }

    public function test_user_can_access_all_their_request_chats()
    {
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user, ['*']);

        // Create multiple requests for the same user
        $request1 = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $request2 = ServiceRequest::factory()->create(['user_id' => $user->id]);

        // Open chats
        $thread1 = $this->postJson('/api/requests/'.$request1->id.'/chat/open')->json('data.threadId');
        $thread2 = $this->postJson('/api/requests/'.$request2->id.'/chat/open')->json('data.threadId');

        // User should be able to access both chats
        $this->getJson('/api/chat/threads/'.$thread1.'/messages')->assertStatus(200);
        $this->getJson('/api/chat/threads/'.$thread2.'/messages')->assertStatus(200);

        // User should be able to post to both chats
        $this->postJson('/api/chat/threads/'.$thread1.'/messages', [
            'type' => 'text',
            'text' => 'Message 1',
        ])->assertStatus(200);

        $this->postJson('/api/chat/threads/'.$thread2.'/messages', [
            'type' => 'text',
            'text' => 'Message 2',
        ])->assertStatus(200);

        // User should be able to close both chats
        $this->patchJson('/api/chat/threads/'.$thread1.'/close')->assertStatus(200);
        $this->patchJson('/api/chat/threads/'.$thread2.'/close')->assertStatus(200);
    }

    public function test_admin_can_access_multiple_user_request_chats()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user1 = User::factory()->create(['role_id' => 2]);
        $user2 = User::factory()->create(['role_id' => 2]);

        Sanctum::actingAs($admin, ['*']);

        // Create requests for different users
        $request1 = ServiceRequest::factory()->create(['user_id' => $user1->id]);
        $request2 = ServiceRequest::factory()->create(['user_id' => $user2->id]);

        // Admin opens chats for both requests
        $thread1 = $this->postJson('/api/requests/'.$request1->id.'/chat/open')->json('data.threadId');
        $thread2 = $this->postJson('/api/requests/'.$request2->id.'/chat/open')->json('data.threadId');

        // Verify different threads
        $this->assertNotEquals($thread1, $thread2);

        // Admin can post to both chats
        $this->postJson('/api/chat/threads/'.$thread1.'/messages', [
            'type' => 'text',
            'text' => 'Admin message to user 1',
        ])->assertStatus(200);

        $this->postJson('/api/chat/threads/'.$thread2.'/messages', [
            'type' => 'text',
            'text' => 'Admin message to user 2',
        ])->assertStatus(200);

        // Verify messages are isolated
        $messages1 = $this->getJson('/api/chat/threads/'.$thread1.'/messages')->json('data.messages');
        $messages2 = $this->getJson('/api/chat/threads/'.$thread2.'/messages')->json('data.messages');

        $this->assertCount(1, $messages1);
        $this->assertEquals('Admin message to user 1', $messages1[0]['text']);

        $this->assertCount(1, $messages2);
        $this->assertEquals('Admin message to user 2', $messages2[0]['text']);
    }

    public function test_chat_thread_uniqueness_constraint()
    {
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user, ['*']);

        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        // Open chat for the same request multiple times
        $open1 = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open2 = $this->postJson('/api/requests/'.$request->id.'/chat/open');
        $open3 = $this->postJson('/api/requests/'.$request->id.'/chat/open');

        $open1->assertStatus(200);
        $open2->assertStatus(200);
        $open3->assertStatus(200);

        $threadId1 = $open1->json('data.threadId');
        $threadId2 = $open2->json('data.threadId');
        $threadId3 = $open3->json('data.threadId');

        // All should return the same thread ID (idempotency)
        $this->assertEquals($threadId1, $threadId2);
        $this->assertEquals($threadId2, $threadId3);

        // Verify only one thread exists in database
        $threadCount = \App\Models\ChatThread::where('request_id', $request->id)->count();
        $this->assertEquals(1, $threadCount, 'Only one chat thread should exist per request');
    }
}