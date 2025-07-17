<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class NotificationsController extends Controller
{
 
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="List user notifications",
     *     description="Retrieve a list of all notifications for the authenticated user.",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Notifications list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="notifications", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="request_updated"),
     *                 @OA\Property(property="data", type="object", example={"request_id": 1, "status": "approved"}),
     *                 @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        // Fetch notifications for the authenticated user
        $notifications = Auth::user()->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['notifications' => $notifications], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/{id}/read",
     *     summary="Mark notification as read",
     *     description="Mark a specific notification as read for the authenticated user.",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Notification ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification marked as read.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     )
     * )
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.'], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     summary="Delete a notification",
     *     description="Delete a specific notification for the authenticated user.",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Notification ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully.'], 200);
    }
}