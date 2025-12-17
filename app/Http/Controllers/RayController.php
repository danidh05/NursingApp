<?php

namespace App\Http\Controllers;

use App\Models\Ray;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Rays",
 *     description="API Endpoints for viewing Rays (User accessible)"
 * )
 */
class RayController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/rays",
     *     summary="List all rays",
     *     description="Retrieve all rays with translations based on Accept-Language header",
     *     tags={"Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar)",
     *         required=false,
     *         @OA\Schema(type="string", example="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rays retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/..."),
     *                 @OA\Property(property="about_ray", type="string", example="Chest X-Ray description...", nullable=true),
     *                 @OA\Property(property="instructions", type="string", example="Follow instructions...", nullable=true),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale();
        $rays = Ray::with('translations')->get();
        
        $rays = $rays->map(function ($ray) use ($locale) {
            $translation = $ray->translate($locale);
            
            return [
                'id' => $ray->id,
                'name' => $translation ? $translation->name : $ray->name,
                'price' => $ray->price,
                'image' => $ray->image_url,
                'about_ray' => $translation?->about_ray,
                'instructions' => $translation?->instructions,
                'additional_information' => $translation?->additional_information,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $rays,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/rays/{id}",
     *     summary="Get a specific ray",
     *     description="Retrieve a specific ray with translations based on Accept-Language header",
     *     tags={"Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ray ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar)",
     *         required=false,
     *         @OA\Schema(type="string", example="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ray retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/..."),
     *                 @OA\Property(property="about_ray", type="string", example="Chest X-Ray description...", nullable=true),
     *                 @OA\Property(property="instructions", type="string", example="Follow instructions...", nullable=true),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Ray not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $locale = app()->getLocale();
        $ray = Ray::with('translations')->findOrFail($id);
        
        $translation = $ray->translate($locale);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ray->id,
                'name' => $translation ? $translation->name : $ray->name,
                'price' => $ray->price,
                'image' => $ray->image_url,
                'about_ray' => $translation?->about_ray,
                'instructions' => $translation?->instructions,
                'additional_information' => $translation?->additional_information,
            ],
        ], 200);
    }
}

