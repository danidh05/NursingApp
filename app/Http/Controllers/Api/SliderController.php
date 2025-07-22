<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SliderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SliderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private SliderService $sliderService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/sliders",
     *     summary="Get homepage sliders",
     *     description="Retrieve all active sliders ordered by position for homepage display. Accessible to both users and admins.",
     *     tags={"Sliders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sliders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="sliders", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", example="https://firebasestorage.googleapis.com/v0/b/.../slider-images/image.jpg", description="Firebase Storage URL for slider image"),
     *                 @OA\Property(property="title", type="string", example="Professional Nursing Care", nullable=true, description="Slider title"),
     *                 @OA\Property(property="subtitle", type="string", example="Trusted by thousands of families", nullable=true, description="Slider subtitle"),
     *                 @OA\Property(property="position", type="integer", example=1, description="Display order position"),
     *                 @OA\Property(property="link", type="string", example="https://example.com", nullable=true, description="Optional click URL"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
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
        $this->authorize('viewAny', \App\Models\Slider::class);
        
        $sliders = $this->sliderService->getAllSliders();
        return response()->json(['sliders' => $sliders], 200);
    }
}