<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrustedImage;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Trusted Images",
 *     description="API Endpoints for managing Trusted Images (Admin only)"
 * )
 */
class TrustedImageController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/trusted-images",
     *     summary="List all trusted images",
     *     tags={"Admin - Trusted Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        $trustedImages = TrustedImage::orderBy('order')->get();
        
        return response()->json([
            'success' => true,
            'data' => $trustedImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image' => $image->image_url,
                    'order' => $image->order,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/trusted-images",
     *     summary="Add a trusted image",
     *     tags={"Admin - Trusted Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="order", type="integer", example=0, description="Display order (optional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
        ]);

        $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'trusted-images');

        $trustedImage = TrustedImage::create([
            'image' => $imagePath,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trusted image added',
            'data' => [
                'id' => $trustedImage->id,
                'image' => $trustedImage->image_url,
                'order' => $trustedImage->order,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/trusted-images/{id}",
     *     summary="Update trusted image",
     *     description="Update a trusted image. Use POST with _method=PUT for file uploads.",
     *     tags={"Admin - Trusted Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="order", type="integer", example=1, description="Display order")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function update(Request $request, TrustedImage $trustedImage): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $trustedImage->image = $this->imageStorageService->updateImage(
                $request->file('image'),
                $trustedImage->image,
                'trusted-images'
            );
        }

        if (isset($validated['order'])) {
            $trustedImage->order = $validated['order'];
        }

        $trustedImage->save();

        return response()->json([
            'success' => true,
            'message' => 'Trusted image updated',
            'data' => [
                'id' => $trustedImage->id,
                'image' => $trustedImage->image_url,
                'order' => $trustedImage->order,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/trusted-images/{id}",
     *     summary="Delete trusted image",
     *     tags={"Admin - Trusted Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy(TrustedImage $trustedImage): JsonResponse
    {
        if ($trustedImage->image) {
            $this->imageStorageService->deleteImage($trustedImage->image);
        }
        $trustedImage->delete();
        return response()->json([
            'success' => true,
            'message' => 'Trusted image deleted',
        ]);
    }
}
