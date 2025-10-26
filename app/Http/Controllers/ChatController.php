<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Request as ServiceRequest;
use App\Services\ChatService;
use App\Services\ChatStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Chat Controller - Temporary Request-Scoped Chat System
 * 
 * This controller implements a temporary, request-scoped chat feature that enables
 * real-time communication between admins and clients during service requests.
 * 
 * Key Features:
 * - Temporary chat threads linked to specific service requests
 * - Support for text, image, and location message types
 * - Real-time WebSocket broadcasting via Laravel Echo/Reverb
 * - Automatic media cleanup on thread closure
 * - Policy-based authorization for thread access
 * - Feature gated behind CHAT_ENABLED environment variable
 * 
 * Message Types:
 * - text: Plain text messages
 * - image: Media files with signed URLs for security
 * - location: GPS coordinates for location sharing
 * 
 * Security:
 * - Private broadcasting channels with authorization
 * - Media path validation to prevent traversal attacks
 * - Signed URLs with configurable TTL
 * - Thread isolation and participant-only access
 * 
 * WEBSOCKET SUBSCRIPTION:
 * After opening a chat thread, subscribe to WebSocket channel to receive real-time events:
 * 
 * Channel: private-chat.{threadId}
 * Events: MessageCreated (listens as "message.created"), ThreadClosed (listens as "thread.closed")
 * 
 * Frontend Implementation (Laravel Echo):
 * Echo.private('private-chat.' + threadId)
 *   .listen('MessageCreated', (event) => handleNewMessage(event))
 *   .listen('ThreadClosed', (event) => handleThreadClosed(event))
 * 
 * Events contain same data structure as API responses (see MessageCreatedEvent and ThreadClosedEvent schemas)
 * 
 * @OA\Tag(
 *     name="Chat",
 *     description="Temporary request-scoped chat endpoints for real-time communication"
 * )
 */
/**
 * @OA\Schema(
 *     schema="ChatMessage",
 *     @OA\Property(property="id", type="integer", example=789, description="Unique message identifier"),
 *     @OA\Property(property="type", type="string", example="text", enum={"text","image","location"}, description="Message type"),
 *     @OA\Property(property="text", type="string", example="Hello, how can I help?", nullable=true, description="Text content for text messages"),
 *     @OA\Property(property="lat", type="number", format="float", example=40.7128, nullable=true, description="Latitude for location messages"),
 *     @OA\Property(property="lng", type="number", format="float", example=-74.0060, nullable=true, description="Longitude for location messages"),
 *     @OA\Property(property="media_url", type="string", example="https://storage.googleapis.com/bucket/chats/123/image.jpg?signed=...", nullable=true, description="Signed URL for image messages (temporary access)"),
 *     @OA\Property(property="sender_id", type="integer", example=1, description="User ID of message sender"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-20T10:30:00Z", description="Message creation timestamp")
 * )
 * 
 * @OA\Schema(
 *     schema="ChatThread",
 *     @OA\Property(property="id", type="integer", example=123, description="Unique thread identifier"),
 *     @OA\Property(property="request_id", type="integer", example=456, description="Associated service request ID"),
 *     @OA\Property(property="admin_id", type="integer", example=1, nullable=true, description="Admin user ID (if admin opened thread)"),
 *     @OA\Property(property="client_id", type="integer", example=2, description="Client user ID"),
 *     @OA\Property(property="status", type="string", example="open", enum={"open","closed"}, description="Thread status"),
 *     @OA\Property(property="opened_at", type="string", format="date-time", example="2025-01-20T10:00:00Z", description="Thread creation timestamp"),
 *     @OA\Property(property="closed_at", type="string", format="date-time", example="2025-01-20T11:00:00Z", nullable=true, description="Thread closure timestamp")
 * )
 * 
 * @OA\Schema(
 *     schema="MessageRequest",
 *     required={"type"},
 *     @OA\Property(property="type", type="string", example="text", enum={"text","image","location"}, description="Message type"),
 *     @OA\Property(property="text", type="string", example="Hello, I need assistance", nullable=true, description="Text content (required for text messages)"),
 *     @OA\Property(property="lat", type="number", format="float", example=40.7128, nullable=true, description="Latitude coordinate (required for location messages)"),
 *     @OA\Property(property="lng", type="number", format="float", example=-74.0060, nullable=true, description="Longitude coordinate (required for location messages)"),
 *     @OA\Property(property="mediaPath", type="string", example="chats/123/image.jpg", nullable=true, description="Storage path for image (required for image messages, must belong to thread)")
 * )
 * 
 * @OA\Schema(
 *     schema="MessageCreatedEvent",
 *     @OA\Property(property="id", type="integer", example=789, description="Message ID"),
 *     @OA\Property(property="type", type="string", example="text", enum={"text","image","location"}, description="Message type"),
 *     @OA\Property(property="text", type="string", example="Hello, how can I help?", nullable=true, description="Text content"),
 *     @OA\Property(property="lat", type="number", format="float", example=40.7128, nullable=true, description="Latitude coordinate"),
 *     @OA\Property(property="lng", type="number", format="float", example=-74.0060, nullable=true, description="Longitude coordinate"),
 *     @OA\Property(property="media_url", type="string", example="https://storage.googleapis.com/bucket/chats/123/image.jpg?signed=...", nullable=true, description="Signed URL for media"),
 *     @OA\Property(property="sender_id", type="integer", example=1, description="Sender user ID"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-20T10:30:00Z", description="Message timestamp")
 * )
 * 
 * @OA\Schema(
 *     schema="ThreadClosedEvent",
 *     @OA\Property(property="thread_id", type="integer", example=123, description="Thread ID that was closed"),
 *     @OA\Property(property="closed_at", type="string", format="date-time", example="2025-01-20T11:00:00Z", description="Closure timestamp")
 * )
 */
class ChatController extends Controller
{
    public function __construct(private ChatService $chatService, private ChatStorageService $storage)
    {
    }

    protected function ensureChatEnabled()
    {
        if (!config('chat.enabled')) {
            abort(501, 'Chat feature is disabled');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/requests/{id}/chat/open",
     *     summary="Open chat thread for service request",
     *     description="Opens a new chat thread for a service request or returns existing thread. Only the request owner or an admin can open a chat thread. Creates a temporary communication channel for real-time messaging during service delivery.",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service request ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat thread opened successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chat thread opened successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="threadId", type="integer", example=123, description="Unique chat thread identifier")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         ref="#/components/responses/Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         ref="#/components/responses/Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service request not found"
     *     ),
     *     @OA\Response(
     *         response=501,
     *         ref="#/components/responses/ChatFeatureDisabled"
     *     )
     * )
     */
    public function open(Request $request, int $id)
    {
        $this->ensureChatEnabled();

        $serviceRequest = ServiceRequest::query()->findOrFail($id);

        // Only the request owner or an admin can open
        $isAdmin = optional($request->user()->role)->name === 'admin';
        if (!$isAdmin && $serviceRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        $thread = $this->chatService->openThread($serviceRequest->id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Chat thread opened successfully',
            'data' => [
                'threadId' => $thread->id
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/chat/threads/{threadId}/messages",
     *     summary="List chat messages with pagination",
     *     description="Retrieves paginated messages from a chat thread. Messages are ordered by ID descending (newest first) and support cursor-based pagination for efficient loading of large conversation histories.",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="threadId",
     *         in="path",
     *         required=true,
     *         description="Chat thread ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         required=false,
     *         description="Message ID for cursor-based pagination (exclusive)",
     *         @OA\Schema(type="integer", example=456)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of messages per page (max 50, default 20)",
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Messages retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="messages", type="array", @OA\Items(ref="#/components/schemas/ChatMessage")),
     *                 @OA\Property(property="nextCursor", type="string", example="123", nullable=true, description="Next cursor for pagination (null if no more messages)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         ref="#/components/responses/Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         ref="#/components/responses/Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat thread not found"
     *     ),
     *     @OA\Response(
     *         response=501,
     *         ref="#/components/responses/ChatFeatureDisabled"
     *     )
     * )
     */
    public function listMessages(Request $request, int $threadId)
    {
        $this->ensureChatEnabled();

        $thread = ChatThread::query()->findOrFail($threadId);
        $this->authorize('view', $thread);

        $cursor = $request->query('cursor');
        $perPage = min(50, max(1, (int)$request->query('limit', 20)));

        $query = ChatMessage::query()->where('thread_id', $thread->id)->orderBy('id', 'desc');
        if ($cursor) {
            $query->where('id', '<', (int)$cursor);
        }

        $messages = $query->limit($perPage)->get();

        $ttl = (int)config('chat.signed_url_ttl');
        $serialized = $messages->map(function (ChatMessage $m) use ($ttl) {
            $mediaUrl = null;
            if ($m->type === 'image' && $m->media_path) {
                $mediaUrl = $this->storage->signGetUrl($m->media_path, $ttl);
            }
            return [
                'id' => $m->id,
                'type' => $m->type,
                'text' => $m->text,
                'lat' => $m->latitude,
                'lng' => $m->longitude,
                'media_url' => $mediaUrl,
                'sender_id' => $m->sender_id,
                'created_at' => $m->created_at,
            ];
        });

        $nextCursor = $messages->last()?->id ? (string)$messages->last()->id : null;

        return response()->json([
            'success' => true,
            'message' => 'Messages retrieved successfully',
            'data' => [
                'messages' => $serialized,
                'nextCursor' => $nextCursor,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chat/threads/{threadId}/upload-url",
     *     summary="Get signed URL for image upload",
     *     description="Retrieves a signed URL for uploading an image directly to storage. The client uses this URL to upload the image, then sends the mediaPath when posting the message.",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="threadId",
     *         in="path",
     *         required=true,
     *         description="Chat thread ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"filename", "contentType"},
     *             @OA\Property(property="filename", type="string", example="image.jpg", description="Original filename (used for generating unique storage path)"),
     *             @OA\Property(property="contentType", type="string", example="image/jpeg", enum={"image/jpeg","image/png","image/webp"}, description="MIME type of the image")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Upload URL generated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Upload URL generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="url", type="string", example="https://storage.googleapis.com/bucket/...", description="Signed URL for uploading"),
     *                 @OA\Property(property="mediaPath", type="string", example="chats/123/unique_filename.jpg", description="Storage path to use when posting the message"),
     *                 @OA\Property(property="headers", type="object", description="HTTP headers to use during upload")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         ref="#/components/responses/Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         ref="#/components/responses/Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat thread not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid content type or missing parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", description="Validation error details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         ref="#/components/responses/ChatFeatureDisabled"
     *     )
     *     )
     */
    public function getUploadUrl(Request $request, int $threadId)
    {
        $this->ensureChatEnabled();

        $thread = ChatThread::query()->findOrFail($threadId);
        Gate::authorize('post', $thread);

        $payload = $request->validate([
            'filename' => 'required|string',
            'contentType' => 'required|string|in:image/jpeg,image/png,image/webp',
        ]);

        // Generate unique filename
        $extension = pathinfo($payload['filename'], PATHINFO_EXTENSION) ?: 'jpg';
        $objectName = "chats/{$threadId}/" . uniqid() . '_' . time() . '.' . $extension;

        $ttl = (int)config('chat.signed_url_ttl');
        $signed = $this->storage->signPutUrl($objectName, $payload['contentType'], $ttl);

        if (!$signed['url']) {
            abort(422, 'Failed to generate upload URL');
        }

        return response()->json([
            'success' => true,
            'message' => 'Upload URL generated successfully',
            'data' => [
                'url' => $signed['url'],
                'mediaPath' => $objectName,
                'headers' => $signed['headers'],
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chat/threads/{threadId}/messages",
     *     summary="Post new message to chat thread",
     *     description="Posts a new message to a chat thread. Supports three message types: text, image, and location. For image messages, mediaPath must be a valid storage path. For location messages, lat and lng coordinates are required. Messages trigger real-time broadcasting to thread participants.",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="threadId",
     *         in="path",
     *         required=true,
     *         description="Chat thread ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MessageRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message posted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message posted successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=789, description="Unique message identifier")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         ref="#/components/responses/Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         ref="#/components/responses/Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat thread not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid message data or media path",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", description="Validation error details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         ref="#/components/responses/ChatFeatureDisabled"
     *     )
     * )
     */
    public function postMessage(Request $request, int $threadId)
    {
        $this->ensureChatEnabled();

        $thread = ChatThread::query()->findOrFail($threadId);
        Gate::authorize('post', $thread);

        $payload = $request->validate([
            'type' => 'required|string|in:text,image,location',
            'text' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'mediaPath' => 'nullable|string',
        ]);

        $message = $this->chatService->postMessage($threadId, $request->user(), $payload);

        return response()->json([
            'success' => true,
            'message' => 'Message posted successfully',
            'data' => [
                'id' => $message->id
            ]
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/chat/threads/{threadId}/close",
     *     summary="Close chat thread and trigger cleanup",
     *     description="Closes a chat thread and triggers automatic cleanup of messages and media. Once closed, no new messages can be posted. Background job processes media deletion and optional message redaction based on configuration.",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="threadId",
     *         in="path",
     *         required=true,
     *         description="Chat thread ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat thread closed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chat thread closed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="closed", enum={"open","closed"}, description="Thread status after closure"),
     *                 @OA\Property(property="closed_at", type="string", format="date-time", example="2025-01-20T11:00:00Z", description="Thread closure timestamp")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         ref="#/components/responses/Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         ref="#/components/responses/Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat thread not found"
     *     ),
     *     @OA\Response(
     *         response=501,
     *         ref="#/components/responses/ChatFeatureDisabled"
     *     )
     * )
     */
    public function close(Request $request, int $threadId)
    {
        $this->ensureChatEnabled();

        $thread = ChatThread::query()->findOrFail($threadId);
        Gate::authorize('close', $thread);

        $thread = $this->chatService->closeThread($threadId, $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Chat thread closed successfully',
            'data' => [
                'status' => $thread->status,
                'closed_at' => $thread->closed_at
            ]
        ]);
    }
}