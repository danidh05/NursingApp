<?php

namespace App\Http\Controllers;

use App\Models\Physiotherapist;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Physiotherapists",
 *     description="API Endpoints for viewing Physiotherapists (User accessible)"
 * )
 */
class PhysiotherapistController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/physiotherapists",
     *     summary="List all physiotherapists",
     *     description="Retrieve all physiotherapists with translations and area-based pricing based on Accept-Language header",
     *     tags={"Physiotherapists"},
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
     *         description="Physiotherapists retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith", description="Physiotherapist name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00, description="Price for user's area (or base price)"),
     *                 @OA\Property(property="base_price", type="number", format="float", example=200.00, description="Base price (fallback)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/physiotherapists/..."),
     *                 @OA\Property(property="job_name", type="string", example="Senior Physiotherapist"),
     *                 @OA\Property(property="job_specification", type="string", example="Musculoskeletal Specialist"),
     *                 @OA\Property(property="specialization", type="string", example="Sports Medicine"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=10),
     *                 @OA\Property(property="description", type="string", example="Experienced physiotherapist...", nullable=true),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="area_name", type="string", example="Beirut"),
     *                     @OA\Property(property="price", type="number", format="float", example=220.00)
     *                 ), description="All area prices for this physiotherapist")
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
        
        $physiotherapists = Physiotherapist::with(['translations', 'areaPrices.area:id,name'])->get();
        
        $physiotherapists = $physiotherapists->map(function ($physiotherapist) use ($locale, $userAreaId) {
            $translation = $physiotherapist->translate($locale);
            
            $areaPrices = $physiotherapist->areaPrices->map(function ($areaPrice) {
                return [
                    'area_id' => $areaPrice->area_id,
                    'area_name' => $areaPrice->area->name ?? null,
                    'price' => $areaPrice->price,
                ];
            });
            
            $userPrice = $physiotherapist->price;
            if ($userAreaId) {
                $userAreaPrice = $physiotherapist->areaPrices->where('area_id', $userAreaId)->first();
                if ($userAreaPrice) {
                    $userPrice = $userAreaPrice->price;
                }
            }
            
            return [
                'id' => $physiotherapist->id,
                'name' => $translation ? $translation->name : $physiotherapist->name,
                'price' => $userPrice,
                'base_price' => $physiotherapist->price,
                'image' => $physiotherapist->image_url,
                'job_name' => $physiotherapist->job_name,
                'job_specification' => $physiotherapist->job_specification,
                'specialization' => $physiotherapist->specialization,
                'years_of_experience' => $physiotherapist->years_of_experience,
                'description' => $translation?->description,
                'area_prices' => $areaPrices,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $physiotherapists,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/physiotherapists/{id}",
     *     summary="Get a specific physiotherapist",
     *     description="Retrieve a specific physiotherapist with translations and area-based pricing",
     *     tags={"Physiotherapists"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist ID",
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
     *         description="Physiotherapist retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="base_price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/physiotherapists/..."),
     *                 @OA\Property(property="job_name", type="string", example="Senior Physiotherapist"),
     *                 @OA\Property(property="job_specification", type="string", example="Musculoskeletal Specialist"),
     *                 @OA\Property(property="specialization", type="string", example="Sports Medicine"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=10),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Physiotherapist not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $locale = app()->getLocale();
        $user = Auth::user();
        $userAreaId = $user->area_id ?? null;
        
        $physiotherapist = Physiotherapist::with(['translations', 'areaPrices.area:id,name'])->findOrFail($id);
        
        $translation = $physiotherapist->translate($locale);
        
        $areaPrices = $physiotherapist->areaPrices->map(function ($areaPrice) {
            return [
                'area_id' => $areaPrice->area_id,
                'area_name' => $areaPrice->area->name ?? null,
                'price' => $areaPrice->price,
            ];
        });
        
        $userPrice = $physiotherapist->price;
        if ($userAreaId) {
            $userAreaPrice = $physiotherapist->areaPrices->where('area_id', $userAreaId)->first();
            if ($userAreaPrice) {
                $userPrice = $userAreaPrice->price;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $physiotherapist->id,
                'name' => $translation ? $translation->name : $physiotherapist->name,
                'price' => $userPrice,
                'base_price' => $physiotherapist->price,
                'image' => $physiotherapist->image_url,
                'job_name' => $physiotherapist->job_name,
                'job_specification' => $physiotherapist->job_specification,
                'specialization' => $physiotherapist->specialization,
                'years_of_experience' => $physiotherapist->years_of_experience,
                'description' => $translation?->description,
                'area_prices' => $areaPrices,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/physiotherapists/area/{area_id}",
     *     summary="Get all physiotherapists for a specific area with pricing",
     *     description="Retrieve all physiotherapists available in a specific area with area-specific pricing when available, fallback to base prices when area pricing doesn't exist. Content is translated based on Accept-Language header.",
     *     tags={"Physiotherapists"},
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
     *         description="Physiotherapists for area retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="price", type="number", format="float", example=220.00),
     *                 @OA\Property(property="base_price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/physiotherapists/..."),
     *                 @OA\Property(property="has_area_pricing", type="boolean", example=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Area not found")
     * )
     */
    public function getPhysiotherapistsByArea($areaId): JsonResponse
    {
        $locale = app()->getLocale();
        
        $area = \App\Models\Area::findOrFail($areaId);
        
        $physiotherapists = Physiotherapist::with([
            'translations',
            'areaPrices' => function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            }
        ])->get();
        
        $physiotherapists = $physiotherapists->map(function ($physiotherapist) use ($locale, $areaId) {
            $translation = $physiotherapist->translate($locale);
            
            $areaPrice = $physiotherapist->areaPrices->first();
            $price = $areaPrice ? $areaPrice->price : $physiotherapist->price;
            $hasAreaPricing = $areaPrice !== null;
            
            return [
                'id' => $physiotherapist->id,
                'name' => $translation ? $translation->name : $physiotherapist->name,
                'price' => $price,
                'base_price' => $physiotherapist->price,
                'image' => $physiotherapist->image_url,
                'job_name' => $physiotherapist->job_name,
                'job_specification' => $physiotherapist->job_specification,
                'specialization' => $physiotherapist->specialization,
                'years_of_experience' => $physiotherapist->years_of_experience,
                'description' => $translation?->description,
                'has_area_pricing' => $hasAreaPricing,
            ];
        });
        
        return response()->json([
            'success' => true,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
            ],
            'data' => $physiotherapists,
        ], 200);
    }
}

