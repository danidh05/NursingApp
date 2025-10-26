<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PopupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PopupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PopupService $popupService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/popups",
     *     summary="Get active popup for app launch",
     *     description="Retrieve the currently active popup for app opening. Returns the most recent active popup where start_date is null or <= now, end_date is null or > now, and is_active = true. Accessible to both users and admins.",
     *     tags={"Popups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active popup retrieved successfully or no active popup available",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="popup", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="image", type="string", nullable=true, example="https://firebasestorage.googleapis.com/v0/b/.../popup-images/image.jpg", description="Firebase Storage URL for popup image"),
     *                         @OA\Property(property="title", type="string", example="Welcome to Our App"),
     *                         @OA\Property(property="content", type="string", example="We are excited to announce our new features..."),
     *                         @OA\Property(property="type", type="string", enum={"info","warning","promo"}, example="info"),
     *                         @OA\Property(property="start_date", type="string", format="date-time", nullable=true, example="2024-01-15T10:00:00Z"),
     *                         @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-01-30T10:00:00Z"),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="popup", type="null", example=null),
     *                     @OA\Property(property="message", type="string", example="No active popup available")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Access denied"
     *     )
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', \App\Models\Popup::class);
        
        $user = auth()->user();
        $popup = $this->popupService->getActivePopup($user);
        
        if ($popup) {
            return response()->json(['popup' => $popup], 200);
        }
        
        return response()->json(['popup' => null, 'message' => 'No active popup available'], 200);
    }
}