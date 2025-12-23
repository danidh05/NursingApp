<?php

namespace App\Http\Controllers;

use App\Models\DoctorCategory;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Doctor Categories",
 *     description="API Endpoints for viewing Doctor Categories (Category 8)"
 * )
 */
class DoctorCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor-categories",
     *     summary="List all doctor categories",
     *     description="Retrieve all doctor categories with translations. Returns category name and image based on Accept-Language header (defaults to 'en').",
     *     tags={"Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doctor categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Cardiology", description="Category name (translated)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/doctor-categories/...", description="Category image URL")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $cats = DoctorCategory::with('translations')->get();
        return response()->json([
            'success' => true,
            'data' => $cats->map(function ($cat) use ($locale) {
                $t = $cat->translate($locale);
                return [
                    'id' => $cat->id,
                    'name' => $t?->name,
                    'image' => $cat->image_url,
                ];
            }),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/doctor-categories/{id}",
     *     summary="Get doctor category details",
     *     description="Retrieve details of a specific doctor category with translations.",
     *     tags={"Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Doctor category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Cardiology", description="Category name (translated)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/doctor-categories/...", description="Category image URL")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Doctor category not found")
     * )
     */
    public function show(DoctorCategory $doctorCategory): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $t = $doctorCategory->translate($locale);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctorCategory->id,
                'name' => $t?->name,
                'image' => $doctorCategory->image_url,
            ],
        ]);
    }
}

