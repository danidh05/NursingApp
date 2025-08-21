<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * User-specific private channel for personal notifications
 * Channel: App.Models.User.{id}
 * Usage: Private user notifications, profile updates, etc.
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
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
 * - Format: private-chat.{threadId}
 * - Example: private-chat.123 for thread ID 123
 * - Frontend subscribes to: Echo.private('chat.123')
 * 
 * AUTHENTICATION:
 * - Requires valid JWT token
 * - User must be participant in the thread
 * - Policy check: $user->can('view', $thread)
 */

/**
 * Private chat channel for real-time messaging
 * 
 * Channel: private-chat.{threadId}
 * Purpose: Real-time message broadcasting for specific chat threads
 * Security: Only thread participants (admin_id or client_id) can subscribe
 * Events: MessageCreated, ThreadClosed
 * 
 * Frontend Usage:
 * ```javascript
 * // Subscribe to chat thread 123
 * Echo.private('chat.123')
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
Broadcast::channel('private-chat.{threadId}', function ($user, $threadId) {
    $thread = \App\Models\ChatThread::query()->find($threadId);
    return $thread && $user->can('view', $thread);
});