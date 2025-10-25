<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;


Broadcast::routes(['middleware' => ['auth:sanctum']]);
/**
 * User-specific private channel for personal notifications
 * Channel: user.{id}
 * Usage: Private user notifications, profile updates, etc.
 */
Broadcast::channel('user.{id}', function ($user, $id) {
    Log::info('[BroadcastAuth] User channel callback', ['user_id' => $user->id, 'channel_id' => $id]);
    return (int) $user->id === (int) $id;
});

/**
 * Admin notifications channel
 * Channel: admin.notifications
 * Usage: Admin dashboard notifications
 */
Broadcast::channel('admin.notifications', function ($user) {
    return $user->role_id === 1; // Admin role
});

/**
 * General notifications channel
 * Channel: notifications
 * Usage: System-wide notifications
 */
Broadcast::channel('notifications', function ($user) {
    return true; // All authenticated users can access
});

/**
 * CHAT REAL-TIME COMMUNICATION CHANNELS
 * 
 * These channels enable real-time messaging between admins and clients
 * during service requests. All channels are private and require authentication.
 * 
 * FRONTEND INTEGRATION:
 * - Subscribe to channels using Laravel Echo
 * - Listen for MessageCreated and ThreadClosed events
 * - Channel names follow pattern: private-chat.{threadId}
 * 
 * SECURITY:
 * - Channels are private (require authentication)
 * - Access controlled by ChatThreadPolicy::view
 * - Only thread participants can subscribe
 * 
 * EVENTS BROADCASTED:
 * - MessageCreated: New message in thread
 * - ThreadClosed: Thread closure notification
 * 
 * CHANNEL NAMING:
 * - Format: private-chat.{threadId} (in frontend requests)
 * - Example: private-chat.123 for thread ID 123
 * - Frontend subscribes to: Echo.private('private-chat.123')
 * - Note: Laravel automatically strips 'private-' prefix when matching channel callbacks
 * 
 * AUTHENTICATION:
 * - Requires valid Sanctum Bearer token
 * - User must be participant in the thread
 * - Policy check: $user->can('view', $thread)
 */

/**
 * Private chat channel for real-time messaging
 * 
 * Channel: chat.{threadId} (registered as chat.{threadId} due to Laravel's private channel handling)
 * Purpose: Real-time message broadcasting for specific chat threads
 * Security: Only thread participants (admin_id or client_id) can subscribe
 * Events: MessageCreated, ThreadClosed
 * 
 * Frontend Usage:
 * ```javascript
 * // Subscribe to chat thread 123
 * Echo.private('private-chat.123')
 *     .listen('MessageCreated', (e) => {
 *         // Handle new message
 *         console.log('New message:', e);
 *     })
 *     .listen('ThreadClosed', (e) => {
 *         // Handle thread closure
 *         console.log('Thread closed:', e);
 *     });
 * ```
 */
Broadcast::channel('chat.{threadId}', function ($user, $threadId) {
    Log::info('[BroadcastAuth] Entered channel callback', [
        'user_id' => $user?->id,
        'thread_id' => $threadId,
    ]);
    
    $thread = \App\Models\ChatThread::find($threadId);
    \Log::info('Broadcast debug', [
        'user' => $user->id,
        'threadId' => $threadId,
        'canView' => $thread ? $user->can('view', $thread) : false,
    ]);
    
    return $thread && $user->can('view', $thread);
});