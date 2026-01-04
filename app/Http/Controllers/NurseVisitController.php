<?php

namespace App\Http\Controllers;

use App\Models\NurseVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Nurse Visits",
 *     description="API Endpoints for viewing Nurse Visits (User)"
 * )
 */
class NurseVisitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/nurse-visits",
     *     summary="Get all nurse visits",
     *     tags={"Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of nurse visits",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nurse Visit"),
     *                     @OA\Property(property="image", type="string", nullable=true),
     *                     @OA\Property(property="price_per_1_visit", type="number", format="float"),
     *                     @OA\Property(property="price_per_2_visits", type="number", format="float"),
     *                     @OA\Property(property="price_per_3_visits", type="number", format="float"),
     *                     @OA\Property(property="price_per_4_visits", type="number", format="float"),
     *                     @OA\Property(
     *                         property="area_prices",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="area_id", type="integer", example=1),
     *                             @OA\Property(property="area_name", type="string", example="Beirut"),
     *                             @OA\Property(property="price_per_1_visit", type="number", format="float"),
     *                             @OA\Property(property="price_per_2_visits", type="number", format="float"),
     *                             @OA\Property(property="price_per_3_visits", type="number", format="float"),
     *                             @OA\Property(property="price_per_4_visits", type="number", format="float")
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
        
        $nurseVisits = NurseVisit::with('areaPrices.area')->get();
        
        return response()->json([
            'success' => true,
            'data' => $nurseVisits->map(function ($nurseVisit) use ($locale) {
                $translation = $nurseVisit->translate($locale);
                return [
                    'id' => $nurseVisit->id,
                    'name' => $nurseVisit->name,
                    'image' => $nurseVisit->image_url,
                    'price_per_1_visit' => $nurseVisit->price_per_1_visit,
                    'price_per_2_visits' => $nurseVisit->price_per_2_visits,
                    'price_per_3_visits' => $nurseVisit->price_per_3_visits,
                    'price_per_4_visits' => $nurseVisit->price_per_4_visits,
                    'area_prices' => $nurseVisit->areaPrices->map(function ($areaPrice) {
                        return [
                            'area_id' => $areaPrice->area_id,
                            'area_name' => $areaPrice->area->name ?? null,
                            'price_per_1_visit' => $areaPrice->price_per_1_visit,
                            'price_per_2_visits' => $areaPrice->price_per_2_visits,
                            'price_per_3_visits' => $areaPrice->price_per_3_visits,
                            'price_per_4_visits' => $areaPrice->price_per_4_visits,
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
     *     path="/api/nurse-visits/{id}",
     *     summary="Get a specific nurse visit",
     *     tags={"Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Nurse visit details")
     * )
     */
    public function show(NurseVisit $nurseVisit): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $nurseVisit->translate($locale);
        
        $nurseVisit->load('areaPrices.area');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $nurseVisit->id,
                'name' => $nurseVisit->name,
                'image' => $nurseVisit->image_url,
                'price_per_1_visit' => $nurseVisit->price_per_1_visit,
                'price_per_2_visits' => $nurseVisit->price_per_2_visits,
                'price_per_3_visits' => $nurseVisit->price_per_3_visits,
                'price_per_4_visits' => $nurseVisit->price_per_4_visits,
                'area_prices' => $nurseVisit->areaPrices->map(function ($areaPrice) {
                    return [
                        'area_id' => $areaPrice->area_id,
                        'area_name' => $areaPrice->area->name ?? null,
                        'price_per_1_visit' => $areaPrice->price_per_1_visit,
                        'price_per_2_visits' => $areaPrice->price_per_2_visits,
                        'price_per_3_visits' => $areaPrice->price_per_3_visits,
                        'price_per_4_visits' => $areaPrice->price_per_4_visits,
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
     *     path="/api/nurse-visits/area/{area_id}",
     *     summary="Get nurse visits by area",
     *     tags={"Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="area_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Nurse visits filtered by area")
     * )
     */
    public function getNurseVisitsByArea(int $areaId): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $nurseVisits = NurseVisit::with(['areaPrices' => function ($query) use ($areaId) {
            $query->where('area_id', $areaId);
        }])->get();
        
        return response()->json([
            'success' => true,
            'data' => $nurseVisits->map(function ($nurseVisit) use ($locale, $areaId) {
                $translation = $nurseVisit->translate($locale);
                $areaPrice = $nurseVisit->areaPrices->first();
                
                return [
                    'id' => $nurseVisit->id,
                    'name' => $nurseVisit->name,
                    'image' => $nurseVisit->image_url,
                    'price_per_1_visit' => $areaPrice ? $areaPrice->price_per_1_visit : $nurseVisit->price_per_1_visit,
                    'price_per_2_visits' => $areaPrice ? $areaPrice->price_per_2_visits : $nurseVisit->price_per_2_visits,
                    'price_per_3_visits' => $areaPrice ? $areaPrice->price_per_3_visits : $nurseVisit->price_per_3_visits,
                    'price_per_4_visits' => $areaPrice ? $areaPrice->price_per_4_visits : $nurseVisit->price_per_4_visits,
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
     *     path="/api/nurse-visits/calculate-price",
     *     summary="Calculate price for nurse visits",
     *     tags={"Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nurse_visit_id", "visits_per_day", "from_date", "to_date"},
     *             @OA\Property(property="nurse_visit_id", type="integer", example=1),
     *             @OA\Property(property="visits_per_day", type="integer", example=2, description="1-4"),
     *             @OA\Property(property="from_date", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="to_date", type="string", format="date", example="2025-01-20"),
     *             @OA\Property(property="area_id", type="integer", example=1, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nurse_visit_id' => 'required|exists:nurse_visits,id',
            'visits_per_day' => 'required|integer|min:1|max:4',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $nurseVisit = NurseVisit::findOrFail($validated['nurse_visit_id']);
        
        $pricePerDay = $nurseVisit->getPriceForVisits($validated['visits_per_day'], $validated['area_id'] ?? null);
        
        $fromDate = \Carbon\Carbon::parse($validated['from_date']);
        $toDate = \Carbon\Carbon::parse($validated['to_date']);
        $numberOfDays = $fromDate->diffInDays($toDate) + 1;
        
        $totalPrice = $pricePerDay * $numberOfDays;

        return response()->json([
            'success' => true,
            'data' => [
                'nurse_visit_id' => $nurseVisit->id,
                'visits_per_day' => $validated['visits_per_day'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'number_of_days' => $numberOfDays,
                'price_per_day' => $pricePerDay,
                'total_price' => $totalPrice,
            ],
        ], 200);
    }
}

