<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Services\SliderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SliderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private SliderService $sliderService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/sliders",
     *     summary="List all sliders (Admin)",
     *     description="Retrieve all sliders for admin management. Accessible only to administrators.",
     *     tags={"Admin - Sliders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sliders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="sliders", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", example="https://firebasestorage.googleapis.com/v0/b/.../slider-images/image.jpg"),
     *                 @OA\Property(property="title", type="string", example="Professional Nursing Care", nullable=true),
     *                 @OA\Property(property="subtitle", type="string", example="Trusted by thousands of families", nullable=true),
     *                 @OA\Property(property="position", type="integer", example=1),
     *                 @OA\Property(property="link", type="string", example="https://example.com", nullable=true),
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
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', \App\Models\Slider::class);
        
        $sliders = $this->sliderService->getAllSliders();
        return response()->json(['sliders' => $sliders], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/sliders",
     *     summary="Create a new slider (Admin)",
     *     description="Create a new slider with image upload. Accessible only to administrators.",
     *     tags={"Admin - Sliders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Slider creation data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"position"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Slider image file (JPG, JPEG, PNG, max 2MB) - Optional"),
     *                 @OA\Property(property="title", type="string", example="Professional Nursing Care", maxLength=255, nullable=true),
     *                 @OA\Property(property="subtitle", type="string", example="Trusted by thousands of families", maxLength=255, nullable=true),
     *                 @OA\Property(property="position", type="integer", example=1, minimum=0, description="Display order position"),
     *                 @OA\Property(property="link", type="string", example="https://example.com", maxLength=500, nullable=true, description="Optional click URL")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Slider created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slider created successfully."),
     *             @OA\Property(property="slider", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="subtitle", type="string"),
     *                 @OA\Property(property="position", type="integer"),
     *                 @OA\Property(property="link", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function store(StoreSliderRequest $request)
    {
        $this->authorize('create', \App\Models\Slider::class);
        
        try {
            $slider = $this->sliderService->createSlider($request->validated());
            
            return response()->json([
                'message' => 'Slider created successfully.',
                'slider' => $slider
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create slider: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/sliders/{id}",
     *     summary="Get specific slider (Admin)",
     *     description="Retrieve a specific slider by ID. Accessible only to administrators.",
     *     tags={"Admin - Sliders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slider retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="slider", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="subtitle", type="string"),
     *                 @OA\Property(property="position", type="integer"),
     *                 @OA\Property(property="link", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slider not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slider not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function show(int $id)
    {
        try {
            $slider = $this->sliderService->getSlider($id);
            $this->authorize('view', $slider);
            
            return response()->json(['slider' => $slider], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Slider not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve slider: ' . $e->getMessage()], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/sliders/{id}",
     *     summary="Update slider (Admin)",
     *     description="Update an existing slider. Image upload is optional. Accessible only to administrators.",
     *     tags={"Admin - Sliders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Slider update data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"position"},
     *                 @OA\Property(property="image", type="string", format="binary", description="New slider image file (optional, JPG, JPEG, PNG, max 2MB)", nullable=true),
     *                 @OA\Property(property="title", type="string", example="Updated Professional Nursing Care", maxLength=255, nullable=true),
     *                 @OA\Property(property="subtitle", type="string", example="Updated subtitle", maxLength=255, nullable=true),
     *                 @OA\Property(property="position", type="integer", example=2, minimum=0, description="Display order position"),
     *                 @OA\Property(property="link", type="string", example="https://updated-example.com", maxLength=500, nullable=true, description="Optional click URL")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slider updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slider updated successfully."),
     *             @OA\Property(property="slider", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="subtitle", type="string"),
     *                 @OA\Property(property="position", type="integer"),
     *                 @OA\Property(property="link", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slider not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slider not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function update(UpdateSliderRequest $request, int $id)
    {
        try {
            $slider = $this->sliderService->getSlider($id);
            $this->authorize('update', $slider);
            
            $slider = $this->sliderService->updateSlider($id, $request->validated());
            
            return response()->json([
                'message' => 'Slider updated successfully.',
                'slider' => $slider
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Slider not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update slider: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/sliders/{id}",
     *     summary="Delete slider (Admin)",
     *     description="Delete a slider and its associated image from Firebase. Accessible only to administrators.",
     *     tags={"Admin - Sliders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slider ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slider deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slider deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slider not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slider not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Failed to delete slider",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to delete slider: [error details]")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        try {
            $slider = $this->sliderService->getSlider($id);
            $this->authorize('delete', $slider);
            
            $this->sliderService->deleteSlider($id);
            
            return response()->json([
                'message' => 'Slider deleted successfully.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Slider not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete slider: ' . $e->getMessage()
            ], 422);
        }
    }
}