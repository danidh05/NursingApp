<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Stream.io Chat",
 *     description="Stream.io chat integration endpoints for real-time messaging"
 * )
 */
class StreamController extends Controller
{
    private StreamService $streamService;

    public function __construct(StreamService $streamService)
    {
        $this->streamService = $streamService;
    }

    /**
     * @OA\Get(
     *     path="/api/stream/token",
     *     summary="Get Stream.io authentication token",
     *     description="Generate a JWT token for Stream.io chat authentication. This token is required by the Flutter frontend to connect to Stream.io chat services. The token contains the user ID and is signed with the Stream.io API secret.",
     *     tags={"Stream.io Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiMTIzIiwiaWF0IjoxNjQwOTk1MjAwLCJleHAiOjE2NDA5OTg4MDB9.signature", description="JWT token for Stream.io authentication"),
     *             @OA\Property(property="api_key", type="string", example="your_stream_api_key", description="Stream.io API key for frontend connection"),
     *             @OA\Property(property="user_id", type="integer", example=123, description="Laravel user ID"),
     *             @OA\Property(property="app_id", type="string", example="your_stream_app_id", description="Stream.io app ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error - Token generation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to generate token"),
     *             @OA\Property(property="message", type="string", example="JWT generation failed")
     *         )
     *     )
     * )
     * 
     * Get Stream.io token for authenticated user
     *
     * @return JsonResponse
     */
    public function getToken(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated'
                ], 401);
            }

            $token = $this->streamService->generateToken($user->id);
            
            return response()->json([
                'token' => $token,
                'api_key' => $this->streamService->getApiKey(),
                'user_id' => $user->id,
                'app_id' => $this->streamService->getAppId(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate token',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/stream/users",
     *     summary="Create user in Stream.io",
     *     description="Create a new user profile in Stream.io chat system. This is typically called after user registration to ensure the user exists in both Laravel and Stream.io systems.",
     *     tags={"Stream.io Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's display name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
     *             @OA\Property(property="image", type="string", format="uri", example="https://example.com/avatar.jpg", description="URL to user's profile image")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User created successfully in Stream.io",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User created successfully in Stream.io"),
     *             @OA\Property(property="data", type="object", description="Stream.io user data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error - Failed to create user in Stream.io",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to create user in Stream.io")
     *         )
     *     )
     * )
     * 
     * Create a new user in Stream.io
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createUser(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated'
                ], 401);
            }

            $userData = $request->only(['name', 'email', 'image']);
            $result = $this->streamService->createUser($user->id, $userData);
            
            if ($result === false) {
                return response()->json([
                    'error' => 'Failed to create user in Stream.io'
                ], 500);
            }

            return response()->json([
                'message' => 'User created successfully in Stream.io',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/stream/users",
     *     summary="Update user in Stream.io",
     *     description="Update an existing user's profile information in Stream.io chat system. This is typically called when a user updates their profile information.",
     *     tags={"Stream.io Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe Updated", description="User's display name"),
     *             @OA\Property(property="email", type="string", format="email", example="john.updated@example.com", description="User's email address"),
     *             @OA\Property(property="image", type="string", format="uri", example="https://example.com/new-avatar.jpg", description="URL to user's profile image")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully in Stream.io",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User updated successfully in Stream.io"),
     *             @OA\Property(property="data", type="object", description="Updated Stream.io user data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error - Failed to update user in Stream.io",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to update user in Stream.io")
     *         )
     *     )
     * )
     * 
     * Update user in Stream.io
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUser(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated'
                ], 401);
            }

            $userData = $request->only(['name', 'email', 'image']);
            $result = $this->streamService->updateUser($user->id, $userData);
            
            if ($result === false) {
                return response()->json([
                    'error' => 'Failed to update user in Stream.io'
                ], 500);
            }

            return response()->json([
                'message' => 'User updated successfully in Stream.io',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/stream/channels",
     *     summary="Create channel in Stream.io",
     *     description="Create a new chat channel in Stream.io. This is typically used for creating group chats, support channels, or any other type of messaging channel.",
     *     tags={"Stream.io Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="channel_type", type="string", example="messaging", description="Type of channel (e.g., messaging, livestream, team, etc.)"),
     *             @OA\Property(property="channel_id", type="string", example="support-channel-123", description="Unique identifier for the channel"),
     *             @OA\Property(property="members", type="array", @OA\Items(type="string"), example={"user1", "user2", "user3"}, description="Array of user IDs to add as members"),
     *             @OA\Property(property="name", type="string", example="Support Channel", description="Display name for the channel"),
     *             @OA\Property(property="description", type="string", example="Customer support channel", description="Channel description"),
     *             @OA\Property(property="image", type="string", format="uri", example="https://example.com/channel-image.jpg", description="URL to channel image")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Channel created successfully in Stream.io",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Channel created successfully in Stream.io"),
     *             @OA\Property(property="data", type="object", description="Stream.io channel data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", description="Validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error - Failed to create channel in Stream.io",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to create channel in Stream.io")
     *         )
     *     )
     * )
     * 
     * Create a channel in Stream.io
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createChannel(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated'
                ], 401);
            }

            $request->validate([
                'channel_type' => 'required|string',
                'channel_id' => 'required|string',
                'members' => 'array',
                'members.*' => 'string',
            ]);

            $channelType = $request->input('channel_type');
            $channelId = $request->input('channel_id');
            $members = $request->input('members', []);
            $channelData = $request->except(['channel_type', 'channel_id', 'members']);

            $result = $this->streamService->createChannel($channelType, $channelId, $members, $channelData);
            
            if ($result === false) {
                return response()->json([
                    'error' => 'Failed to create channel in Stream.io'
                ], 500);
            }

            return response()->json([
                'message' => 'Channel created successfully in Stream.io',
                'data' => $result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create channel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/stream/channels/members",
     *     summary="Add members to channel",
     *     description="Add one or more users to an existing Stream.io channel. This is useful for inviting users to group chats or adding new members to support channels.",
     *     tags={"Stream.io Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="channel_type", type="string", example="messaging", description="Type of channel"),
     *             @OA\Property(property="channel_id", type="string", example="support-channel-123", description="Channel identifier"),
     *             @OA\Property(property="members", type="array", @OA\Items(type="string"), example={"user4", "user5"}, description="Array of user IDs to add as members")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Members added successfully to channel",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Members added successfully to channel in Stream.io"),
     *             @OA\Property(property="data", type="object", description="Updated channel data with new members")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", description="Validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error - Failed to add members to channel",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to add members to channel in Stream.io")
     *         )
     *     )
     * )
     * 
     * Add members to a channel
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addChannelMembers(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated'
                ], 401);
            }

            $request->validate([
                'channel_type' => 'required|string',
                'channel_id' => 'required|string',
                'members' => 'required|array',
                'members.*' => 'string',
            ]);

            $channelType = $request->input('channel_type');
            $channelId = $request->input('channel_id');
            $members = $request->input('members');

            $result = $this->streamService->addChannelMembers($channelType, $channelId, $members);
            
            if ($result === false) {
                return response()->json([
                    'error' => 'Failed to add members to channel in Stream.io'
                ], 500);
            }

            return response()->json([
                'message' => 'Members added successfully to channel in Stream.io',
                'data' => $result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add members to channel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/stream/messages",
     *     summary="Send message to channel",
     *     description="Send a text message to a specific Stream.io channel. This is typically used for system-generated messages or automated notifications.",
     *     tags={"Stream.io Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="channel_type", type="string", example="messaging", description="Type of channel"),
     *             @OA\Property(property="channel_id", type="string", example="support-channel-123", description="Channel identifier"),
     *             @OA\Property(property="message", type="string", example="Hello! This is a system message.", description="Message text content"),
     *             @OA\Property(property="metadata", type="object", description="Additional message metadata"),
     *             @OA\Property(property="attachments", type="array", @OA\Items(type="object"), description="Message attachments"),
     *             @OA\Property(property="parent_id", type="string", example="msg-123", description="ID of parent message for replies")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message sent successfully in Stream.io"),
     *             @OA\Property(property="data", type="object", description="Stream.io message data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", description="Validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error - Failed to send message",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to send message in Stream.io")
     *         )
     *     )
     * )
     * 
     * Send a message to a channel
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated'
                ], 401);
            }

            $request->validate([
                'channel_type' => 'required|string',
                'channel_id' => 'required|string',
                'message' => 'required|string',
            ]);

            $channelType = $request->input('channel_type');
            $channelId = $request->input('channel_id');
            $message = $request->input('message');
            $additionalData = $request->except(['channel_type', 'channel_id', 'message']);

            $result = $this->streamService->sendMessage($channelType, $channelId, $message, $user->id, $additionalData);
            
            if ($result === false) {
                return response()->json([
                    'error' => 'Failed to send message in Stream.io'
                ], 500);
            }

            return response()->json([
                'message' => 'Message sent successfully in Stream.io',
                'data' => $result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send message',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}