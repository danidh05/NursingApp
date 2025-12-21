<?php

namespace App\Http\Controllers;

use App\Models\Duty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Duties",
 *     description="API Endpoints for viewing Duties (User)"
 * )
 */
class DutyController extends Controller
{
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $duties = Duty::with('areaPrices.area')->get();
        
        return response()->json([
            'success' => true,
            'data' => $duties->map(function ($duty) use ($locale) {
                $translation = $duty->translate($locale);
                return [
                    'id' => $duty->id,
                    'name' => $duty->name,
                    'image' => $duty->image_url,
                    'day_shift_price_4_hours' => $duty->day_shift_price_4_hours,
                    'day_shift_price_6_hours' => $duty->day_shift_price_6_hours,
                    'day_shift_price_8_hours' => $duty->day_shift_price_8_hours,
                    'day_shift_price_12_hours' => $duty->day_shift_price_12_hours,
                    'night_shift_price_4_hours' => $duty->night_shift_price_4_hours,
                    'night_shift_price_6_hours' => $duty->night_shift_price_6_hours,
                    'night_shift_price_8_hours' => $duty->night_shift_price_8_hours,
                    'night_shift_price_12_hours' => $duty->night_shift_price_12_hours,
                    'continuous_care_price' => $duty->continuous_care_price,
                    'area_prices' => $duty->areaPrices->map(function ($areaPrice) {
                        return [
                            'area_id' => $areaPrice->area_id,
                            'area_name' => $areaPrice->area->name ?? null,
                            'day_shift_price_4_hours' => $areaPrice->day_shift_price_4_hours,
                            'day_shift_price_6_hours' => $areaPrice->day_shift_price_6_hours,
                            'day_shift_price_8_hours' => $areaPrice->day_shift_price_8_hours,
                            'day_shift_price_12_hours' => $areaPrice->day_shift_price_12_hours,
                            'night_shift_price_4_hours' => $areaPrice->night_shift_price_4_hours,
                            'night_shift_price_6_hours' => $areaPrice->night_shift_price_6_hours,
                            'night_shift_price_8_hours' => $areaPrice->night_shift_price_8_hours,
                            'night_shift_price_12_hours' => $areaPrice->night_shift_price_12_hours,
                            'continuous_care_price' => $areaPrice->continuous_care_price,
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

    public function show(Duty $duty): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $duty->translate($locale);
        
        $duty->load('areaPrices.area');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $duty->id,
                'name' => $duty->name,
                'image' => $duty->image_url,
                'day_shift_price_4_hours' => $duty->day_shift_price_4_hours,
                'day_shift_price_6_hours' => $duty->day_shift_price_6_hours,
                'day_shift_price_8_hours' => $duty->day_shift_price_8_hours,
                'day_shift_price_12_hours' => $duty->day_shift_price_12_hours,
                'night_shift_price_4_hours' => $duty->night_shift_price_4_hours,
                'night_shift_price_6_hours' => $duty->night_shift_price_6_hours,
                'night_shift_price_8_hours' => $duty->night_shift_price_8_hours,
                'night_shift_price_12_hours' => $duty->night_shift_price_12_hours,
                'continuous_care_price' => $duty->continuous_care_price,
                'area_prices' => $duty->areaPrices->map(function ($areaPrice) {
                    return [
                        'area_id' => $areaPrice->area_id,
                        'area_name' => $areaPrice->area->name ?? null,
                        'day_shift_price_4_hours' => $areaPrice->day_shift_price_4_hours,
                        'day_shift_price_6_hours' => $areaPrice->day_shift_price_6_hours,
                        'day_shift_price_8_hours' => $areaPrice->day_shift_price_8_hours,
                        'day_shift_price_12_hours' => $areaPrice->day_shift_price_12_hours,
                        'night_shift_price_4_hours' => $areaPrice->night_shift_price_4_hours,
                        'night_shift_price_6_hours' => $areaPrice->night_shift_price_6_hours,
                        'night_shift_price_8_hours' => $areaPrice->night_shift_price_8_hours,
                        'night_shift_price_12_hours' => $areaPrice->night_shift_price_12_hours,
                        'continuous_care_price' => $areaPrice->continuous_care_price,
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

    public function getDutiesByArea(int $areaId): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $duties = Duty::with(['areaPrices' => function ($query) use ($areaId) {
            $query->where('area_id', $areaId);
        }])->get();
        
        return response()->json([
            'success' => true,
            'data' => $duties->map(function ($duty) use ($locale, $areaId) {
                $translation = $duty->translate($locale);
                $areaPrice = $duty->areaPrices->first();
                
                return [
                    'id' => $duty->id,
                    'name' => $duty->name,
                    'image' => $duty->image_url,
                    'day_shift_price_4_hours' => $areaPrice ? $areaPrice->day_shift_price_4_hours : $duty->day_shift_price_4_hours,
                    'day_shift_price_6_hours' => $areaPrice ? $areaPrice->day_shift_price_6_hours : $duty->day_shift_price_6_hours,
                    'day_shift_price_8_hours' => $areaPrice ? $areaPrice->day_shift_price_8_hours : $duty->day_shift_price_8_hours,
                    'day_shift_price_12_hours' => $areaPrice ? $areaPrice->day_shift_price_12_hours : $duty->day_shift_price_12_hours,
                    'night_shift_price_4_hours' => $areaPrice ? $areaPrice->night_shift_price_4_hours : $duty->night_shift_price_4_hours,
                    'night_shift_price_6_hours' => $areaPrice ? $areaPrice->night_shift_price_6_hours : $duty->night_shift_price_6_hours,
                    'night_shift_price_8_hours' => $areaPrice ? $areaPrice->night_shift_price_8_hours : $duty->night_shift_price_8_hours,
                    'night_shift_price_12_hours' => $areaPrice ? $areaPrice->night_shift_price_12_hours : $duty->night_shift_price_12_hours,
                    'continuous_care_price' => $areaPrice ? $areaPrice->continuous_care_price : $duty->continuous_care_price,
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
            'duty_id' => 'required|exists:duties,id',
            'duration_hours' => 'nullable|integer|in:4,6,8,12',
            'is_continuous_care' => 'nullable|boolean',
            'is_day_shift' => 'nullable|boolean',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $duty = Duty::findOrFail($validated['duty_id']);
        
        if ($validated['is_continuous_care'] ?? false) {
            $totalPrice = $duty->getContinuousCarePrice($validated['area_id'] ?? null);
        } else {
            $durationHours = $validated['duration_hours'];
            if (!$durationHours) {
                return response()->json([
                    'success' => false,
                    'message' => 'duration_hours is required unless is_continuous_care is true',
                ], 422);
            }
            
            $pricePerDay = $duty->getPriceForDuration($durationHours, $validated['is_day_shift'] ?? true, $validated['area_id'] ?? null);
            $fromDate = \Carbon\Carbon::parse($validated['from_date']);
            $toDate = \Carbon\Carbon::parse($validated['to_date']);
            $numberOfDays = $fromDate->diffInDays($toDate) + 1;
            
            $totalPrice = $pricePerDay * $numberOfDays;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'duty_id' => $duty->id,
                'duration_hours' => $validated['duration_hours'] ?? null,
                'is_continuous_care' => $validated['is_continuous_care'] ?? false,
                'is_day_shift' => $validated['is_day_shift'] ?? true,
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'number_of_days' => isset($validated['from_date']) && isset($validated['to_date']) ? \Carbon\Carbon::parse($validated['from_date'])->diffInDays(\Carbon\Carbon::parse($validated['to_date'])) + 1 : null,
                'price_per_day' => ($validated['is_continuous_care'] ?? false) ? null : $duty->getPriceForDuration($validated['duration_hours'] ?? 12, $validated['is_day_shift'] ?? true, $validated['area_id'] ?? null),
                'total_price' => $totalPrice,
            ],
        ], 200);
    }
}

