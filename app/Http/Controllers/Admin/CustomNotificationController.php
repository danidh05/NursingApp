<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\CustomNotificationSent;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CustomNotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Post(
     *     path="/api/admin/notifications/custom",
     *     summary="Send custom notification to user",
     *     description="Admin can send a custom notification to any user",
     *     tags={"Admin - Custom Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "title", "message"},
     *             @OA\Property(property="user_id", type="integer", example=1, description="ID of the user to send notification to"),
     *             @OA\Property(property="title", type="string", example="Welcome Bonus", description="Notification title"),
     *             @OA\Property(property="message", type="string", example="You have received a special welcome bonus!", description="Notification content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Custom notification sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Custom notification sent successfully"),
     *             @OA\Property(property="notification", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="type", type="string", example="custom"),
     *                 @OA\Property(property="sent_by_admin_id", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000'
        ]);

        $user = User::findOrFail($validated['user_id']);
        $admin = Auth::user();

        // Trigger the event - the listener will handle the actual notification creation
        event(new CustomNotificationSent($user, $admin, $validated['title'], $validated['message']));

        return response()->json([
            'message' => 'Custom notification sent successfully',
            'user_id' => $user->id,
            'title' => $validated['title']
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/notifications/custom",
     *     summary="List custom notifications sent by admin",
     *     description="Get all custom notifications sent by the authenticated admin",
     *     tags={"Admin - Custom Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Custom notifications list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="notifications", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="type", type="string", example="custom"),
     *                 @OA\Property(property="sent_by_admin_id", type="integer"),
     *                 @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 )
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $admin = Auth::user();
        $notifications = $this->notificationService->getCustomNotificationsSentByAdmin($admin);

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/notifications/users",
     *     summary="List all users for notification targeting",
     *     description="Get a list of all users that admin can send notifications to",
     *     tags={"Admin - Custom Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="users", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function getUsers(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }
} 