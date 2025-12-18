<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Machines",
 *     description="API Endpoints for viewing Machines (User accessible)"
 * )
 */
class MachineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/machines",
     *     summary="List all machines",
     *     description="Retrieve all machines with translations and area-based pricing based on Accept-Language header",
     *     tags={"Machines"},
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
     *         description="Machines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00, description="Price for user's area (or base price)"),
     *                 @OA\Property(property="base_price", type="number", format="float", example=500.00, description="Base price (fallback)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/..."),
     *                 @OA\Property(property="description", type="string", example="Machine description...", nullable=true),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="area_name", type="string", example="Beirut"),
     *                     @OA\Property(property="price", type="number", format="float", example=600.00)
     *                 ), description="All area prices for this machine")
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
        
        $machines = Machine::with(['translations', 'areaPrices.area:id,name'])->get();
        
        $machines = $machines->map(function ($machine) use ($locale, $userAreaId) {
            $translation = $machine->translate($locale);
            
            // Get all area prices for this machine
            $areaPrices = $machine->areaPrices->map(function ($areaPrice) {
                return [
                    'area_id' => $areaPrice->area_id,
                    'area_name' => $areaPrice->area->name ?? null,
                    'price' => $areaPrice->price,
                ];
            });
            
            // Get user's area price if available, otherwise use base price
            $userPrice = $machine->price; // Default to base price
            if ($userAreaId) {
                $userAreaPrice = $machine->areaPrices->where('area_id', $userAreaId)->first();
                if ($userAreaPrice) {
                    $userPrice = $userAreaPrice->price;
                }
            }
            
            return [
                'id' => $machine->id,
                'name' => $translation ? $translation->name : $machine->name,
                'price' => $userPrice, // Price for user's area (or base price)
                'base_price' => $machine->price, // Base price (fallback)
                'image' => $machine->image_url,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
                'area_prices' => $areaPrices, // All area prices for this machine
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $machines,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/machines/{id}",
     *     summary="Get a specific machine",
     *     description="Retrieve a specific machine with translations and area-based pricing based on Accept-Language header",
     *     tags={"Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Machine ID",
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
     *         description="Machine retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00, description="Price for user's area (or base price)"),
     *                 @OA\Property(property="base_price", type="number", format="float", example=500.00, description="Base price (fallback)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/..."),
     *                 @OA\Property(property="description", type="string", example="Machine description...", nullable=true),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="area_name", type="string", example="Beirut"),
     *                     @OA\Property(property="price", type="number", format="float", example=600.00)
     *                 ), description="All area prices for this machine")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Machine not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $locale = app()->getLocale();
        $user = Auth::user();
        $userAreaId = $user->area_id ?? null;
        
        $machine = Machine::with(['translations', 'areaPrices.area:id,name'])->findOrFail($id);
        
        $translation = $machine->translate($locale);
        
        // Get all area prices for this machine
        $areaPrices = $machine->areaPrices->map(function ($areaPrice) {
            return [
                'area_id' => $areaPrice->area_id,
                'area_name' => $areaPrice->area->name ?? null,
                'price' => $areaPrice->price,
            ];
        });
        
        // Get user's area price if available, otherwise use base price
        $userPrice = $machine->price; // Default to base price
        if ($userAreaId) {
            $userAreaPrice = $machine->areaPrices->where('area_id', $userAreaId)->first();
            if ($userAreaPrice) {
                $userPrice = $userAreaPrice->price;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $machine->id,
                'name' => $translation ? $translation->name : $machine->name,
                'price' => $userPrice, // Price for user's area (or base price)
                'base_price' => $machine->price, // Base price (fallback)
                'image' => $machine->image_url,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
                'area_prices' => $areaPrices, // All area prices for this machine
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/machines/area/{area_id}",
     *     summary="Get all machines for a specific area with pricing",
     *     description="Retrieve all machines available in a specific area with area-specific pricing when available, fallback to base prices when area pricing doesn't exist. Content is translated based on Accept-Language header.",
     *     tags={"Machines"},
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
     *         description="Machines for area retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=600.00, description="Area-specific price if available, otherwise base price"),
     *                 @OA\Property(property="base_price", type="number", format="float", example=500.00, description="Base price (fallback)"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/..."),
     *                 @OA\Property(property="description", type="string", example="Machine description...", nullable=true),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true),
     *                 @OA\Property(property="has_area_pricing", type="boolean", example=true, description="Whether this machine has area-specific pricing")
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
    public function getMachinesByArea($areaId): JsonResponse
    {
        $locale = app()->getLocale();
        
        // Verify area exists
        $area = \App\Models\Area::findOrFail($areaId);
        
        $machines = Machine::with([
            'translations',
            'areaPrices' => function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            }
        ])->get();
        
        $machines = $machines->map(function ($machine) use ($locale, $areaId) {
            $translation = $machine->translate($locale);
            
            // Get area-specific price
            $areaPrice = $machine->areaPrices->first();
            $price = $areaPrice ? $areaPrice->price : $machine->price;
            $hasAreaPricing = $areaPrice !== null;
            
            return [
                'id' => $machine->id,
                'name' => $translation ? $translation->name : $machine->name,
                'price' => $price,
                'base_price' => $machine->price,
                'image' => $machine->image_url,
                'description' => $translation?->description,
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
            'data' => $machines,
        ], 200);
    }
}

