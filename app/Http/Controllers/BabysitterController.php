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
                    'day_shift_price_24_hours' => $babysitter->day_shift_price_24_hours,
                    'night_shift_price_12_hours' => $babysitter->night_shift_price_12_hours,
                    'night_shift_price_24_hours' => $babysitter->night_shift_price_24_hours,
                    'area_prices' => $babysitter->areaPrices->map(function ($areaPrice) {
                        return [
                            'area_id' => $areaPrice->area_id,
                            'area_name' => $areaPrice->area->name ?? null,
                            'day_shift_price_12_hours' => $areaPrice->day_shift_price_12_hours,
                            'day_shift_price_24_hours' => $areaPrice->day_shift_price_24_hours,
                            'night_shift_price_12_hours' => $areaPrice->night_shift_price_12_hours,
                            'night_shift_price_24_hours' => $areaPrice->night_shift_price_24_hours,
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
                'day_shift_price_24_hours' => $babysitter->day_shift_price_24_hours,
                'night_shift_price_12_hours' => $babysitter->night_shift_price_12_hours,
                'night_shift_price_24_hours' => $babysitter->night_shift_price_24_hours,
                'area_prices' => $babysitter->areaPrices->map(function ($areaPrice) {
                    return [
                        'area_id' => $areaPrice->area_id,
                        'area_name' => $areaPrice->area->name ?? null,
                        'day_shift_price_12_hours' => $areaPrice->day_shift_price_12_hours,
                        'day_shift_price_24_hours' => $areaPrice->day_shift_price_24_hours,
                        'night_shift_price_12_hours' => $areaPrice->night_shift_price_12_hours,
                        'night_shift_price_24_hours' => $areaPrice->night_shift_price_24_hours,
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

