<?php

namespace App\Http\Controllers;

use App\Models\Babysitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Babysitters",
 *     description="API Endpoints for viewing Babysitters (User)"
 * )
 */
class BabysitterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/babysitters",
     *     summary="Get all babysitters",
     *     tags={"Babysitters"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of babysitters",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Baby Sitter"),
     *                     @OA\Property(property="image", type="string", nullable=true),
     *                     @OA\Property(property="day_shift_price_12_hours", type="number", format="float"),
     *                     @OA\Property(property="day_shift_price_24_hours", type="number", format="float", description="Deprecated: Use price_24_hours instead"),
     *                     @OA\Property(property="night_shift_price_12_hours", type="number", format="float"),
     *                     @OA\Property(property="night_shift_price_24_hours", type="number", format="float", description="Deprecated: Use price_24_hours instead"),
     *                     @OA\Property(property="price_24_hours", type="number", format="float", description="24-hour shift price (separate from day/night, not day/night specific) - USE THIS"),
     *                     @OA\Property(
     *                         property="area_prices",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="area_id", type="integer", example=1),
     *                             @OA\Property(property="area_name", type="string", example="Beirut"),
     *                             @OA\Property(property="day_shift_price_12_hours", type="number", format="float"),
     *                             @OA\Property(property="day_shift_price_24_hours", type="number", format="float", description="Deprecated: Use price_24_hours instead"),
     *                             @OA\Property(property="night_shift_price_12_hours", type="number", format="float"),
     *                             @OA\Property(property="night_shift_price_24_hours", type="number", format="float", description="Deprecated: Use price_24_hours instead"),
     *                             @OA\Property(property="price_24_hours", type="number", format="float", description="24-hour shift price (separate from day/night) - USE THIS")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $babysitters = Babysitter::with('areaPrices.area')->get();
        
        return response()->json([
            'success' => true,
            'data' => $babysitters->map(function ($babysitter) use ($locale) {
                $translation = $babysitter->translate($locale);
                return [
                    'id' => $babysitter->id,
                    'name' => $babysitter->name,
                    'image' => $babysitter->image_url,
                    'day_shift_price_12_hours' => $babysitter->day_shift_price_12_hours,
                    'day_shift_price_24_hours' => $babysitter->day_shift_price_24_hours, // Deprecated
                    'night_shift_price_12_hours' => $babysitter->night_shift_price_12_hours,
                    'night_shift_price_24_hours' => $babysitter->night_shift_price_24_hours, // Deprecated
                    'price_24_hours' => $babysitter->price_24_hours,
                    'area_prices' => $babysitter->areaPrices->map(function ($areaPrice) {
                        return [
                            'area_id' => $areaPrice->area_id,
                            'area_name' => $areaPrice->area->name ?? null,
                            'day_shift_price_12_hours' => $areaPrice->day_shift_price_12_hours,
                            'day_shift_price_24_hours' => $areaPrice->day_shift_price_24_hours, // Deprecated
                            'night_shift_price_12_hours' => $areaPrice->night_shift_price_12_hours,
                            'night_shift_price_24_hours' => $areaPrice->night_shift_price_24_hours, // Deprecated
                            'price_24_hours' => $areaPrice->price_24_hours,
                        ];
                    }),
                    'about' => $translation?->about,
                    'terms_and_conditions' => $translation?->terms_and_conditions,
                    'additional_instructions' => $translation?->additional_instructions,
                    'service_include' => $translation?->service_include,
                    'description' => $translation?->description,
                    'additional_information' => $translation?->additional_information,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/babysitters/{id}",
     *     summary="Get a specific babysitter",
     *     tags={"Babysitters"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Babysitter details")
     * )
     */
    public function show(Babysitter $babysitter): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $babysitter->translate($locale);
        
        $babysitter->load('areaPrices.area');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $babysitter->id,
                'name' => $babysitter->name,
                'image' => $babysitter->image_url,
                'day_shift_price_12_hours' => $babysitter->day_shift_price_12_hours,
                'day_shift_price_24_hours' => $babysitter->day_shift_price_24_hours, // Deprecated
                'night_shift_price_12_hours' => $babysitter->night_shift_price_12_hours,
                'night_shift_price_24_hours' => $babysitter->night_shift_price_24_hours, // Deprecated
                'price_24_hours' => $babysitter->price_24_hours,
                'area_prices' => $babysitter->areaPrices->map(function ($areaPrice) {
                    return [
                        'area_id' => $areaPrice->area_id,
                        'area_name' => $areaPrice->area->name ?? null,
                        'day_shift_price_12_hours' => $areaPrice->day_shift_price_12_hours,
                        'day_shift_price_24_hours' => $areaPrice->day_shift_price_24_hours, // Deprecated
                        'night_shift_price_12_hours' => $areaPrice->night_shift_price_12_hours,
                        'night_shift_price_24_hours' => $areaPrice->night_shift_price_24_hours, // Deprecated
                        'price_24_hours' => $areaPrice->price_24_hours,
                    ];
                }),
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/babysitters/area/{area_id}",
     *     summary="Get babysitters by area",
     *     tags={"Babysitters"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="area_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Babysitters filtered by area")
     * )
     */
    public function getBabysittersByArea(int $areaId): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $babysitters = Babysitter::with(['areaPrices' => function ($query) use ($areaId) {
            $query->where('area_id', $areaId);
        }])->get();
        
        return response()->json([
            'success' => true,
            'data' => $babysitters->map(function ($babysitter) use ($locale, $areaId) {
                $translation = $babysitter->translate($locale);
                $areaPrice = $babysitter->areaPrices->first();
                
                return [
                    'id' => $babysitter->id,
                    'name' => $babysitter->name,
                    'image' => $babysitter->image_url,
                    'day_shift_price_12_hours' => $areaPrice ? $areaPrice->day_shift_price_12_hours : $babysitter->day_shift_price_12_hours,
                    'day_shift_price_24_hours' => $areaPrice ? $areaPrice->day_shift_price_24_hours : $babysitter->day_shift_price_24_hours,
                    'night_shift_price_12_hours' => $areaPrice ? $areaPrice->night_shift_price_12_hours : $babysitter->night_shift_price_12_hours,
                    'night_shift_price_24_hours' => $areaPrice ? $areaPrice->night_shift_price_24_hours : $babysitter->night_shift_price_24_hours,
                    'about' => $translation?->about,
                    'terms_and_conditions' => $translation?->terms_and_conditions,
                    'additional_instructions' => $translation?->additional_instructions,
                    'service_include' => $translation?->service_include,
                    'description' => $translation?->description,
                    'additional_information' => $translation?->additional_information,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/babysitters/calculate-price",
     *     summary="Calculate price for babysitters",
     *     tags={"Babysitters"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"babysitter_id","duration_hours","from_date","to_date"},
     *             @OA\Property(property="babysitter_id", type="integer", example=1),
     *             @OA\Property(property="duration_hours", type="integer", example=12, description="12 or 24"),
     *             @OA\Property(property="is_day_shift", type="boolean", example=true),
     *             @OA\Property(property="from_date", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="to_date", type="string", format="date", example="2025-01-05"),
     *             @OA\Property(property="area_id", type="integer", example=1, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Calculated price")
     * )
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'babysitter_id' => 'required|exists:babysitters,id',
            'duration_hours' => 'required|integer|in:12,24',
            'is_day_shift' => 'nullable|boolean',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $babysitter = Babysitter::findOrFail($validated['babysitter_id']);
        
        $pricePerDay = $babysitter->getPriceForDuration($validated['duration_hours'], $validated['is_day_shift'] ?? true, $validated['area_id'] ?? null);
        $fromDate = \Carbon\Carbon::parse($validated['from_date']);
        $toDate = \Carbon\Carbon::parse($validated['to_date']);
        $numberOfDays = $fromDate->diffInDays($toDate) + 1;
        
        $totalPrice = $pricePerDay * $numberOfDays;

        return response()->json([
            'success' => true,
            'data' => [
                'babysitter_id' => $babysitter->id,
                'duration_hours' => $validated['duration_hours'],
                'is_day_shift' => $validated['is_day_shift'] ?? true,
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'number_of_days' => $numberOfDays,
                'price_per_day' => $pricePerDay,
                'total_price' => $totalPrice,
            ],
        ], 200);
    }
}

