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
        $user = Auth::user();
        $userAreaId = $user->area_id ?? null;
        
        $rays = Ray::with(['translations', 'areaPrices.area:id,name'])->get();
        
        $rays = $rays->map(function ($ray) use ($locale, $userAreaId) {
            $translation = $ray->translate($locale);
            
            // Get all area prices for this ray
            $areaPrices = $ray->areaPrices->map(function ($areaPrice) {
                return [
                    'area_id' => $areaPrice->area_id,
                    'area_name' => $areaPrice->area->name ?? null,
                    'price' => $areaPrice->price,
                ];
            });
            
            // Get user's area price if available, otherwise use base price
            $userPrice = $ray->price; // Default to base price
            if ($userAreaId) {
                $userAreaPrice = $ray->areaPrices->where('area_id', $userAreaId)->first();
                if ($userAreaPrice) {
                    $userPrice = $userAreaPrice->price;
                }
            }
            
            return [
                'id' => $ray->id,
                'name' => $translation ? $translation->name : $ray->name,
                'price' => $userPrice, // Price for user's area (or base price)
                'base_price' => $ray->price, // Base price (fallback)
                'image' => $ray->image_url,
                'about_ray' => $translation?->about_ray,
                'instructions' => $translation?->instructions,
                'additional_information' => $translation?->additional_information,
                'area_prices' => $areaPrices, // All area prices for this ray
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
        $user = Auth::user();
        $userAreaId = $user->area_id ?? null;
        
        $ray = Ray::with(['translations', 'areaPrices.area:id,name'])->findOrFail($id);
        
        $translation = $ray->translate($locale);
        
        // Get all area prices for this ray
        $areaPrices = $ray->areaPrices->map(function ($areaPrice) {
            return [
                'area_id' => $areaPrice->area_id,
                'area_name' => $areaPrice->area->name ?? null,
                'price' => $areaPrice->price,
            ];
        });
        
        // Get user's area price if available, otherwise use base price
        $userPrice = $ray->price; // Default to base price
        if ($userAreaId) {
            $userAreaPrice = $ray->areaPrices->where('area_id', $userAreaId)->first();
            if ($userAreaPrice) {
                $userPrice = $userAreaPrice->price;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ray->id,
                'name' => $translation ? $translation->name : $ray->name,
                'price' => $userPrice, // Price for user's area (or base price)
                'base_price' => $ray->price, // Base price (fallback)
                'image' => $ray->image_url,
                'about_ray' => $translation?->about_ray,
                'instructions' => $translation?->instructions,
                'additional_information' => $translation?->additional_information,
                'area_prices' => $areaPrices, // All area prices for this ray
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/rays/area/{area_id}",
     *     summary="Get all rays for a specific area with pricing",
     *     description="Retrieve all rays available in a specific area with area-specific pricing when available, fallback to base prices when area pricing doesn't exist. Content is translated based on Accept-Language header.",
     *     tags={"Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="area_id",
     *         in="path",
     *         required=true,
     *         description="Area ID",
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
     *         description="Rays for area retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00, description="Area-specific price if available, otherwise base price"),
     *                 @OA\Property(property="base_price", type="number", format="float", example=100.00, description="Base price (fallback)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/..."),
     *                 @OA\Property(property="about_ray", type="string", example="Chest X-Ray description...", nullable=true),
     *                 @OA\Property(property="instructions", type="string", example="Follow instructions...", nullable=true),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true),
     *                 @OA\Property(property="has_area_pricing", type="boolean", example=true, description="Whether this ray has area-specific pricing")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Area not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getRaysByArea($areaId): JsonResponse
    {
        $locale = app()->getLocale();
        
        // Verify area exists
        $area = \App\Models\Area::findOrFail($areaId);
        
        $rays = Ray::with([
            'translations',
            'areaPrices' => function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            }
        ])->get();
        
        $rays = $rays->map(function ($ray) use ($locale, $areaId) {
            $translation = $ray->translate($locale);
            
            // Get area-specific price
            $areaPrice = $ray->areaPrices->first();
            $price = $areaPrice ? $areaPrice->price : $ray->price;
            $hasAreaPricing = $areaPrice !== null;
            
            return [
                'id' => $ray->id,
                'name' => $translation ? $translation->name : $ray->name,
                'price' => $price,
                'base_price' => $ray->price,
                'image' => $ray->image_url,
                'about_ray' => $translation?->about_ray,
                'instructions' => $translation?->instructions,
                'additional_information' => $translation?->additional_information,
                'has_area_pricing' => $hasAreaPricing,
            ];
        });
        
        return response()->json([
            'success' => true,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
            ],
            'data' => $rays,
        ], 200);
    }
}

