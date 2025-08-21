<?php

namespace Tests\Unit;

use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Role;
use App\Models\Request as ServiceRequest;
use App\Jobs\CloseChatAndPurgeMediaJob;
use App\Services\ChatStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CloseChatAndPurgeMediaJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('chat.enabled', true);
        $this->app['config']->set('chat.redact_messages', true);
        
        // Seed roles
        Role::create(['id' => 1, 'name' => 'admin']);
        Role::create(['id' => 2, 'name' => 'user']);
    }

    public function test_job_redacts_messages_when_enabled()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        // Create messages with different types
        $textMessage = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'text' => 'This should be redacted',
        ]);

        $imageMessage = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'type' => 'image',
            'media_path' => 'chats/'.$thread->id.'/test.jpg',
        ]);

        $locationMessage = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'type' => 'location',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
        ]);

        // Run the job
        $job = new CloseChatAndPurgeMediaJob($thread->id);
        $job->handle($this->app->make(ChatStorageService::class));

        // Refresh models
        $textMessage->refresh();
        $imageMessage->refresh();
        $locationMessage->refresh();

        // Verify redaction
        $this->assertNull($textMessage->text);
        $this->assertNull($imageMessage->media_path);
        $this->assertNull($locationMessage->latitude);
        $this->assertNull($locationMessage->longitude);
    }

    public function test_job_preserves_messages_when_redaction_disabled()
    {
        $this->app['config']->set('chat.redact_messages', false);
        
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $textMessage = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'text' => 'This should be preserved',
        ]);

        // Run the job
        $job = new CloseChatAndPurgeMediaJob($thread->id);
        $job->handle($this->app->make(ChatStorageService::class));

        // Refresh model
        $textMessage->refresh();

        // Verify preservation
        $this->assertEquals('This should be preserved', $textMessage->text);
    }

    public function test_job_handles_nonexistent_thread()
    {
        $job = new CloseChatAndPurgeMediaJob(99999);
        
        // Should not throw exception
        $job->handle($this->app->make(ChatStorageService::class));
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_job_retry_configuration()
    {
        $job = new CloseChatAndPurgeMediaJob(1);
        
        $this->assertEquals(5, $job->tries);
        $this->assertEquals([5, 30, 60, 120, 300], $job->backoff());
    }
}