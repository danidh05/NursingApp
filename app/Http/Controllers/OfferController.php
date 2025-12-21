<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Offers",
 *     description="API Endpoints for viewing Offers (User accessible)"
 * )
 */
class OfferController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/offers",
     *     summary="List all offers",
     *     description="Retrieve all offers with translations and area-based pricing based on Accept-Language header",
     *     tags={"Offers"},
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
     *         description="Offers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Special Service Offer", description="Offer name (translatable)"),
     *                 @OA\Property(property="offer_price", type="number", format="float", example=10.00, description="Offer price for user's area (or base price)"),
     *                 @OA\Property(property="old_price", type="number", format="float", example=20.00, description="Old price for user's area (or base price)"),
     *                 @OA\Property(property="base_offer_price", type="number", format="float", example=10.00, description="Base offer price (fallback)"),
     *                 @OA\Property(property="base_old_price", type="number", format="float", example=20.00, description="Base old price (fallback)"),
     *                 @OA\Property(property="offer_available_until", type="string", example="3 Days"),
     *                 @OA\Property(property="category_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/offers/..."),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="area_name", type="string", example="Beirut"),
     *                     @OA\Property(property="offer_price", type="number", format="float", example=12.00),
     *                     @OA\Property(property="old_price", type="number", format="float", example=25.00)
     *                 ), description="All area prices for this offer")
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
        
        $offers = Offer::with(['translations', 'areaPrices.area:id,name', 'category'])->get();
        
        $offers = $offers->map(function ($offer) use ($locale, $userAreaId) {
            $translation = $offer->translate($locale);
            
            $areaPrices = $offer->areaPrices->map(function ($areaPrice) {
                return [
                    'area_id' => $areaPrice->area_id,
                    'area_name' => $areaPrice->area->name ?? null,
                    'offer_price' => $areaPrice->offer_price,
                    'old_price' => $areaPrice->old_price,
                ];
            });
            
            $userOfferPrice = $offer->offer_price;
            $userOldPrice = $offer->old_price;
            if ($userAreaId) {
                $userAreaPrice = $offer->areaPrices->where('area_id', $userAreaId)->first();
                if ($userAreaPrice) {
                    $userOfferPrice = $userAreaPrice->offer_price;
                    $userOldPrice = $userAreaPrice->old_price;
                }
            }
            
            return [
                'id' => $offer->id,
                'name' => $translation ? $translation->name : $offer->name,
                'offer_price' => $userOfferPrice,
                'old_price' => $userOldPrice,
                'base_offer_price' => $offer->offer_price,
                'base_old_price' => $offer->old_price,
                'offer_available_until' => $offer->offer_available_until,
                'category_id' => $offer->category_id,
                'category' => $offer->category ? [
                    'id' => $offer->category->id,
                    'name' => $offer->category->name,
                ] : null,
                'image' => $offer->image_url,
                'description' => $translation?->description,
                'area_prices' => $areaPrices,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $offers,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/offers/{id}",
     *     summary="Get a specific offer",
     *     description="Retrieve a specific offer with translations and area-based pricing",
     *     tags={"Offers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Offer ID",
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
     *         description="Offer retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Offer not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $locale = app()->getLocale();
        $user = Auth::user();
        $userAreaId = $user->area_id ?? null;
        
        $offer = Offer::with(['translations', 'areaPrices.area:id,name', 'category'])->findOrFail($id);
        
        $translation = $offer->translate($locale);
        
        $areaPrices = $offer->areaPrices->map(function ($areaPrice) {
            return [
                'area_id' => $areaPrice->area_id,
                'area_name' => $areaPrice->area->name ?? null,
                'offer_price' => $areaPrice->offer_price,
                'old_price' => $areaPrice->old_price,
            ];
        });
        
        $userOfferPrice = $offer->offer_price;
        $userOldPrice = $offer->old_price;
        if ($userAreaId) {
            $userAreaPrice = $offer->areaPrices->where('area_id', $userAreaId)->first();
            if ($userAreaPrice) {
                $userOfferPrice = $userAreaPrice->offer_price;
                $userOldPrice = $userAreaPrice->old_price;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $offer->id,
                'name' => $translation ? $translation->name : $offer->name,
                'offer_price' => $userOfferPrice,
                'old_price' => $userOldPrice,
                'base_offer_price' => $offer->offer_price,
                'base_old_price' => $offer->old_price,
                'offer_available_until' => $offer->offer_available_until,
                'category_id' => $offer->category_id,
                'category' => $offer->category ? [
                    'id' => $offer->category->id,
                    'name' => $offer->category->name,
                ] : null,
                'image' => $offer->image_url,
                'description' => $translation?->description,
                'area_prices' => $areaPrices,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/offers/area/{area_id}",
     *     summary="Get all offers for a specific area with pricing",
     *     description="Retrieve all offers available in a specific area with area-specific pricing when available, fallback to base prices when area pricing doesn't exist. Content is translated based on Accept-Language header.",
     *     tags={"Offers"},
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
     *         description="Offers for area retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Special Service Offer"),
     *                 @OA\Property(property="offer_price", type="number", format="float", example=12.00),
     *                 @OA\Property(property="old_price", type="number", format="float", example=25.00),
     *                 @OA\Property(property="base_offer_price", type="number", format="float", example=10.00),
     *                 @OA\Property(property="base_old_price", type="number", format="float", example=20.00),
     *                 @OA\Property(property="has_area_pricing", type="boolean", example=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Area not found")
     * )
     */
    public function getOffersByArea($areaId): JsonResponse
    {
        $locale = app()->getLocale();
        
        $area = \App\Models\Area::findOrFail($areaId);
        
        $offers = Offer::with([
            'translations',
            'areaPrices' => function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            },
            'category'
        ])->get();
        
        $offers = $offers->map(function ($offer) use ($locale, $areaId) {
            $translation = $offer->translate($locale);
            
            $areaPrice = $offer->areaPrices->first();
            $offerPrice = $areaPrice ? $areaPrice->offer_price : $offer->offer_price;
            $oldPrice = $areaPrice ? $areaPrice->old_price : $offer->old_price;
            $hasAreaPricing = $areaPrice !== null;
            
            return [
                'id' => $offer->id,
                'name' => $translation ? $translation->name : $offer->name,
                'offer_price' => $offerPrice,
                'old_price' => $oldPrice,
                'base_offer_price' => $offer->offer_price,
                'base_old_price' => $offer->old_price,
                'offer_available_until' => $offer->offer_available_until,
                'category_id' => $offer->category_id,
                'category' => $offer->category ? [
                    'id' => $offer->category->id,
                    'name' => $offer->category->name,
                ] : null,
                'image' => $offer->image_url,
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
            'data' => $offers,
        ], 200);
    }
}

