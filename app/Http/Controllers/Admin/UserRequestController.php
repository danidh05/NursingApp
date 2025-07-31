<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Request;
use Illuminate\Http\JsonResponse;

class UserRequestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users/{userId}/requests",
     *     summary="Get user's request history (Admin)",
     *     description="View all requests for a specific user to help admin decide on discount application. Shows total sessions and spending to help with discount decisions.",
     *     tags={"Admin - User Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User requests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="total_requests", type="integer", example=7),
     *                 @OA\Property(property="completed_requests", type="integer", example=5),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=650.00),
     *                 @OA\Property(property="total_savings", type="number", format="float", example=85.00),
     *                 @OA\Property(property="discounted_requests", type="integer", example=2)
     *             ),
     *             @OA\Property(property="requests", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="total_price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="discount_percentage", type="number", format="float", nullable=true, example=10.00),
     *                 @OA\Property(property="discounted_price", type="number", format="float", example=135.00),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="services", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Home Nursing")
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function getUserRequests(int $userId): JsonResponse
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $requests = Request::where('user_id', $userId)
                          ->with(['services', 'nurse'])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($request) {
                              return [
                                  'id' => $request->id,
                                  'status' => $request->status,
                                  'total_price' => $request->total_price,
                                  'discount_percentage' => $request->discount_percentage,
                                  'discounted_price' => $request->discounted_price,
                                  'final_price' => $request->getFinalPrice(),
                                  'discount_amount' => $request->getDiscountAmount(),
                                  'has_discount' => $request->hasDiscount(),
                                  'created_at' => $request->created_at,
                                  'scheduled_time' => $request->scheduled_time,
                                  'services' => $request->services->map(fn($s) => [
                                      'id' => $s->id,
                                      'name' => $s->name
                                  ]),
                                  'nurse' => $request->nurse ? [
                                      'id' => $request->nurse->id,
                                      'name' => $request->nurse->name
                                  ] : null
                              ];
                          });

        $userStats = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'total_requests' => $requests->count(),
            'completed_requests' => $requests->where('status', 'completed')->count(),
            'total_spent' => $requests->sum('final_price'),
            'total_savings' => $requests->sum('discount_amount'),
            'discounted_requests' => $requests->where('has_discount', true)->count(),
            'average_request_value' => $requests->count() > 0 ? $requests->avg('total_price') : 0,
        ];

        return response()->json([
            'user' => $userStats,
            'requests' => $requests->values()
        ], 200);
    }
} 