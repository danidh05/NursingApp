<?php

namespace Tests\Unit;

use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Models\Role;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Services\ChatService;
use App\Services\ChatStorageService;
use App\Jobs\CloseChatAndPurgeMediaJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('chat.enabled', true);
        $this->app['config']->set('chat.signed_url_ttl', 900);
        $this->app['config']->set('chat.redact_messages', true);
        
        // Seed roles
        Role::create(['id' => 1, 'name' => 'admin']);
        Role::create(['id' => 2, 'name' => 'user']);
        
        Queue::fake();
        Event::fake();
    }

    public function test_open_and_post_text_message()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $storage = $this->app->make(ChatStorageService::class);
        $service = $this->app->make(ChatService::class);

        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $thread = $service->openThread($request->id, $user);
        $this->assertNotNull($thread->id);
        $this->assertEquals($user->id, $thread->client_id);
        $this->assertEquals('open', $thread->status);

        $msg = $service->postMessage($thread->id, $user, [
            'type' => 'text',
            'text' => 'hello',
        ]);
        $this->assertEquals('text', $msg->type);
        $this->assertEquals('hello', $msg->text);
    }

    public function test_open_thread_idempotency()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $thread1 = $service->openThread($request->id, $user);
        $thread2 = $service->openThread($request->id, $user);
        
        $this->assertEquals($thread1->id, $thread2->id);
        $this->assertEquals($thread1->request_id, $thread2->request_id);
    }

    public function test_post_image_message()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $msg = $service->postMessage($thread->id, $user, [
            'type' => 'image',
            'mediaPath' => "chats/{$thread->id}/test_image.jpg",
        ]);
        
        $this->assertEquals('image', $msg->type);
        $this->assertEquals("chats/{$thread->id}/test_image.jpg", $msg->media_path);
    }

    public function test_post_location_message()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $msg = $service->postMessage($thread->id, $user, [
            'type' => 'location',
            'lat' => 34.0522,
            'lng' => -118.2437,
        ]);
        
        $this->assertEquals('location', $msg->type);
        $this->assertEquals(34.0522, $msg->latitude);
        $this->assertEquals(-118.2437, $msg->longitude);
    }

    public function test_close_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $closedThread = $service->closeThread($thread->id, $user);
        
        $this->assertEquals('closed', $closedThread->status);
        $this->assertNotNull($closedThread->closed_at);
        
        Queue::assertPushed(CloseChatAndPurgeMediaJob::class);
    }

    public function test_close_thread_idempotency()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $closedThread1 = $service->closeThread($thread->id, $user);
        $closedThread2 = $service->closeThread($thread->id, $user);
        
        $this->assertEquals($closedThread1->id, $closedThread2->id);
        $this->assertEquals('closed', $closedThread2->status);
    }

    public function test_invalid_message_type()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid message type');
        
        $service->postMessage($thread->id, $user, [
            'type' => 'invalid',
            'text' => 'should fail',
        ]);
    }

    public function test_empty_text_message()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Text is required');
        
        $service->postMessage($thread->id, $user, [
            'type' => 'text',
            'text' => '',
        ]);
    }

    public function test_invalid_location_coordinates()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid coordinates');
        
        $service->postMessage($thread->id, $user, [
            'type' => 'location',
            'lat' => 'invalid',
            'lng' => 'invalid',
        ]);
    }

    public function test_invalid_media_path()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid media path for this thread');
        
        $service->postMessage($thread->id, $user, [
            'type' => 'image',
            'mediaPath' => 'invalid/path.jpg',
        ]);
    }

    public function test_feature_disabled()
    {
        $this->app['config']->set('chat.enabled', false);
        
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Chat feature is disabled');
        
        $service->openThread($request->id, $user);
    }

    public function test_admin_can_open_thread()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($admin);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $thread = $service->openThread($request->id, $admin);
        
        $this->assertEquals($admin->id, $thread->admin_id);
        $this->assertEquals($user->id, $thread->client_id);
    }

    public function test_thread_relationships()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        // Test relationships
        $this->assertInstanceOf(ServiceRequest::class, $thread->request);
        $this->assertInstanceOf(User::class, $thread->client);
        $this->assertEquals($user->id, $thread->client->id);
    }

    public function test_message_relationships()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);
        
        $service = $this->app->make(ChatService::class);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        $thread = $service->openThread($request->id, $user);

        $message = $service->postMessage($thread->id, $user, [
            'type' => 'text',
            'text' => 'test message',
        ]);

        // Test relationships
        $this->assertInstanceOf(ChatThread::class, $message->thread);
        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($user->id, $message->sender->id);
    }
}