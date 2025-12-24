<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class UserRequestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users/{userId}/requests",
     *     summary="Get user's request history with filtering (Admin)",
     *     description="View all requests for a specific user to help admin decide on discount application. Shows total sessions and spending to help with discount decisions. Supports filtering by status and insurance requests.",
     *     tags={"Admin - User Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by request status",
     *         @OA\Schema(type="string", enum={"submitted","assigned","in_progress","completed","canceled"}, example="completed")
     *     ),
     *     @OA\Parameter(
     *         name="request_with_insurance",
     *         in="query",
     *         required=false,
     *         description="Filter by insurance requests (true/false). Only applies to Category 2 (Tests) requests.",
     *         @OA\Schema(type="string", enum={"true","false","1","0"}, example="true")
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
     *                 @OA\Property(property="thread_id", type="integer", example=123, nullable=true, description="Chat thread ID for admin-user communication"),
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
    public function getUserRequests(HttpRequest $httpRequest, int $userId): JsonResponse
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Build query with filters
        $query = Request::where('user_id', $userId)
                          ->with(['services', 'nurse', 'chatThread']);

        // Apply status filter
        if ($httpRequest->has('status')) {
            $query->where('status', $httpRequest->query('status'));
        }

        // Apply insurance filter (only for Category 2: Tests)
        if ($httpRequest->has('request_with_insurance')) {
            $insuranceValue = filter_var($httpRequest->query('request_with_insurance'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($insuranceValue !== null) {
                $query->where('request_with_insurance', $insuranceValue);
            }
        }

        $requests = $query->orderBy('created_at', 'desc')
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
                                  'thread_id' => $request->chatThread?->id ?? null,
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

        // Calculate stats from ALL user requests (not filtered)
        $allRequests = Request::where('user_id', $userId)->get();
        $userStats = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'total_requests' => $allRequests->count(),
            'completed_requests' => $allRequests->where('status', 'completed')->count(),
            'total_spent' => $allRequests->sum(function ($r) { return $r->getFinalPrice(); }),
            'total_savings' => $allRequests->sum(function ($r) { return $r->getDiscountAmount(); }),
            'discounted_requests' => $allRequests->filter(function ($r) { return $r->hasDiscount(); })->count(),
            'average_request_value' => $allRequests->count() > 0 ? $allRequests->avg('total_price') : 0,
        ];

        return response()->json([
            'user' => $userStats,
            'requests' => $requests->values()
        ], 200);
    }
} 