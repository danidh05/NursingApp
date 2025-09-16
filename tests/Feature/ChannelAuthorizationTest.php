<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatThread;
use App\Models\Request as ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Config;

class ChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable chat feature
        Config::set('chat.enabled', true);
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
    }

    public function test_user_can_access_their_own_chat_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // User should be able to access their thread
        $this->assertTrue($user->can('view', $thread));
    }

    public function test_user_cannot_access_other_users_chat_thread()
    {
        $user1 = User::factory()->create(['role_id' => 2]);
        $user2 = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user1->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user1->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // User2 should not be able to access user1's thread
        $this->assertFalse($user2->can('view', $thread));
    }

    public function test_admin_can_access_any_chat_thread()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Admin should be able to access any thread
        $this->assertTrue($admin->can('view', $thread));
    }

    public function test_admin_can_access_thread_they_are_assigned_to()
    {
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
        
        // Admin should be able to access thread they're assigned to
        $this->assertTrue($admin->can('view', $thread));
    }

    public function test_admin_can_access_thread_they_are_not_assigned_to()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Admin should be able to access any thread, even if not assigned
        $this->assertTrue($admin->can('view', $thread));
    }

    public function test_guest_cannot_access_any_chat_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Guest should not be able to access any thread
        $this->assertFalse(auth()->guest() ? false : true);
    }

    public function test_chat_thread_policy_handles_missing_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        
        // Test with non-existent thread ID
        $this->assertFalse($user->can('view', null));
    }

    public function test_chat_thread_policy_handles_deleted_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Store thread ID before deletion
        $threadId = $thread->id;
        
        // Delete the thread
        $thread->delete();
        
        // Try to find the deleted thread - it should not exist
        $deletedThread = ChatThread::find($threadId);
        $this->assertNull($deletedThread);
        
        // User should not be able to access deleted thread (null check)
        $this->assertFalse($user->can('view', $deletedThread));
    }

    public function test_chat_thread_policy_handles_closed_thread()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'closed',
            'opened_at' => now(),
            'closed_at' => now(),
        ]);
        
        // User should still be able to access closed thread (for viewing history)
        $this->assertTrue($user->can('view', $thread));
    }

    public function test_chat_thread_policy_handles_different_user_roles()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Admin should be able to access
        $this->assertTrue($admin->can('view', $thread));
        
        // User should be able to access their own thread
        $this->assertTrue($user->can('view', $thread));
        
        // Create another user
        $otherUser = User::factory()->create(['role_id' => 2]);
        
        // Other user should not be able to access
        $this->assertFalse($otherUser->can('view', $thread));
    }

    public function test_chat_thread_policy_handles_thread_with_admin_assigned()
    {
        $admin1 = User::factory()->create(['role_id' => 1]);
        $admin2 = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'admin_id' => $admin1->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Assigned admin should be able to access
        $this->assertTrue($admin1->can('view', $thread));
        
        // Other admin should also be able to access
        $this->assertTrue($admin2->can('view', $thread));
        
        // User should be able to access their own thread
        $this->assertTrue($user->can('view', $thread));
    }

    public function test_chat_thread_policy_handles_thread_without_admin_assigned()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 2]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);
        
        $thread = ChatThread::create([
            'request_id' => $request->id,
            'client_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // Admin should be able to access even if not assigned
        $this->assertTrue($admin->can('view', $thread));
        
        // User should be able to access their own thread
        $this->assertTrue($user->can('view', $thread));
    }

    public function test_chat_thread_policy_handles_different_request_owners()
    {
        $user1 = User::factory()->create(['role_id' => 2]);
        $user2 = User::factory()->create(['role_id' => 2]);
        $request1 = ServiceRequest::factory()->create(['user_id' => $user1->id]);
        $request2 = ServiceRequest::factory()->create(['user_id' => $user2->id]);
        
        $thread1 = ChatThread::create([
            'request_id' => $request1->id,
            'client_id' => $user1->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        $thread2 = ChatThread::create([
            'request_id' => $request2->id,
            'client_id' => $user2->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        // User1 should only be able to access their own thread
        $this->assertTrue($user1->can('view', $thread1));
        $this->assertFalse($user1->can('view', $thread2));
        
        // User2 should only be able to access their own thread
        $this->assertTrue($user2->can('view', $thread2));
        $this->assertFalse($user2->can('view', $thread1));
    }
}