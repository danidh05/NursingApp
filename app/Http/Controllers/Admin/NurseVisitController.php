<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\NurseVisit;
use App\Models\NurseVisitAreaPrice;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Nurse Visits",
 *     description="API Endpoints for managing Nurse Visits (Admin only)"
 * )
 */
class NurseVisitController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/nurse-visits",
     *     summary="List all nurse visits",
     *     tags={"Admin - Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $nurseVisits = NurseVisit::all();
        
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
     *     path="/api/admin/nurse-visits",
     *     summary="Create a new nurse visit",
     *     tags={"Admin - Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"price_per_1_visit", "price_per_2_visits", "price_per_3_visits", "price_per_4_visits"},
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="price_per_1_visit", type="number", format="float"),
     *                 @OA\Property(property="price_per_2_visits", type="number", format="float"),
     *                 @OA\Property(property="price_per_3_visits", type="number", format="float"),
     *                 @OA\Property(property="price_per_4_visits", type="number", format="float"),
     *                 @OA\Property(property="locale", type="string", example="en", description="Optional, defaults to 'en'"),
     *                 @OA\Property(property="about", type="string"),
     *                 @OA\Property(property="terms_and_conditions", type="string"),
     *                 @OA\Property(property="additional_instructions", type="string"),
     *                 @OA\Property(property="service_include", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="additional_information", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'price_per_1_visit' => 'required|numeric|min:0',
            'price_per_2_visits' => 'required|numeric|min:0',
            'price_per_3_visits' => 'required|numeric|min:0',
            'price_per_4_visits' => 'required|numeric|min:0',
            'locale' => 'nullable|string|in:en,ar',
            'about' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'additional_instructions' => 'nullable|string',
            'service_include' => 'nullable|string',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        $locale = $validated['locale'] ?? 'en';

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'nurse-visits');
        }

        // Create nurse visit
        $nurseVisit = NurseVisit::create([
            'name' => 'Nurse Visit',
            'image' => $imagePath,
            'price_per_1_visit' => $validated['price_per_1_visit'],
            'price_per_2_visits' => $validated['price_per_2_visits'],
            'price_per_3_visits' => $validated['price_per_3_visits'],
            'price_per_4_visits' => $validated['price_per_4_visits'],
        ]);

        // Create translation
        $nurseVisit->translations()->create([
            'locale' => $locale,
            'about' => $validated['about'] ?? null,
            'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            'additional_instructions' => $validated['additional_instructions'] ?? null,
            'service_include' => $validated['service_include'] ?? null,
            'description' => $validated['description'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        // Create area prices for all areas (using base prices)
        $areas = Area::all();
        foreach ($areas as $area) {
            NurseVisitAreaPrice::create([
                'nurse_visit_id' => $nurseVisit->id,
                'area_id' => $area->id,
                'price_per_1_visit' => $validated['price_per_1_visit'],
                'price_per_2_visits' => $validated['price_per_2_visits'],
                'price_per_3_visits' => $validated['price_per_3_visits'],
                'price_per_4_visits' => $validated['price_per_4_visits'],
            ]);
        }

        $translation = $nurseVisit->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Nurse visit created successfully',
            'data' => [
                'id' => $nurseVisit->id,
                'name' => $nurseVisit->name,
                'image' => $nurseVisit->image_url,
                'price_per_1_visit' => $nurseVisit->price_per_1_visit,
                'price_per_2_visits' => $nurseVisit->price_per_2_visits,
                'price_per_3_visits' => $nurseVisit->price_per_3_visits,
                'price_per_4_visits' => $nurseVisit->price_per_4_visits,
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/nurse-visits/{id}",
     *     summary="Get a specific nurse visit",
     *     tags={"Admin - Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(NurseVisit $nurseVisit): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $nurseVisit->translate($locale);

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
     * @OA\Put(
     *     path="/api/admin/nurse-visits/{id}",
     *     summary="Update a nurse visit",
     *     tags={"Admin - Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="price_per_1_visit", type="number", format="float"),
     *                 @OA\Property(property="price_per_2_visits", type="number", format="float"),
     *                 @OA\Property(property="price_per_3_visits", type="number", format="float"),
     *                 @OA\Property(property="price_per_4_visits", type="number", format="float"),
     *                 @OA\Property(property="locale", type="string", example="en"),
     *                 @OA\Property(property="about", type="string"),
     *                 @OA\Property(property="terms_and_conditions", type="string"),
     *                 @OA\Property(property="additional_instructions", type="string"),
     *                 @OA\Property(property="service_include", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="additional_information", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, NurseVisit $nurseVisit): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'price_per_1_visit' => 'nullable|numeric|min:0',
            'price_per_2_visits' => 'nullable|numeric|min:0',
            'price_per_3_visits' => 'nullable|numeric|min:0',
            'price_per_4_visits' => 'nullable|numeric|min:0',
            'locale' => 'nullable|string|in:en,ar',
            'about' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'additional_instructions' => 'nullable|string',
            'service_include' => 'nullable|string',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        $locale = $validated['locale'] ?? 'en';

        // Update image if provided
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $nurseVisit->image,
                'nurse-visits'
            );
            $nurseVisit->image = $imagePath;
        }

        // Update prices if provided
        if (isset($validated['price_per_1_visit'])) {
            $nurseVisit->price_per_1_visit = $validated['price_per_1_visit'];
        }
        if (isset($validated['price_per_2_visits'])) {
            $nurseVisit->price_per_2_visits = $validated['price_per_2_visits'];
        }
        if (isset($validated['price_per_3_visits'])) {
            $nurseVisit->price_per_3_visits = $validated['price_per_3_visits'];
        }
        if (isset($validated['price_per_4_visits'])) {
            $nurseVisit->price_per_4_visits = $validated['price_per_4_visits'];
        }

        $nurseVisit->save();

        // Update or create translation
        $translation = $nurseVisit->translations()->where('locale', $locale)->first();
        if ($translation) {
            $translation->update([
                'about' => $validated['about'] ?? $translation->about,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? $translation->terms_and_conditions,
                'additional_instructions' => $validated['additional_instructions'] ?? $translation->additional_instructions,
                'service_include' => $validated['service_include'] ?? $translation->service_include,
                'description' => $validated['description'] ?? $translation->description,
                'additional_information' => $validated['additional_information'] ?? $translation->additional_information,
            ]);
        } else {
            $nurseVisit->translations()->create([
                'locale' => $locale,
                'about' => $validated['about'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'additional_instructions' => $validated['additional_instructions'] ?? null,
                'service_include' => $validated['service_include'] ?? null,
                'description' => $validated['description'] ?? null,
                'additional_information' => $validated['additional_information'] ?? null,
            ]);
        }

        $translation = $nurseVisit->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Nurse visit updated successfully',
            'data' => [
                'id' => $nurseVisit->id,
                'name' => $nurseVisit->name,
                'image' => $nurseVisit->image_url,
                'price_per_1_visit' => $nurseVisit->price_per_1_visit,
                'price_per_2_visits' => $nurseVisit->price_per_2_visits,
                'price_per_3_visits' => $nurseVisit->price_per_3_visits,
                'price_per_4_visits' => $nurseVisit->price_per_4_visits,
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
     * @OA\Delete(
     *     path="/api/admin/nurse-visits/{id}",
     *     summary="Delete a nurse visit",
     *     tags={"Admin - Nurse Visits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(NurseVisit $nurseVisit): JsonResponse
    {
        // Delete image if exists
        if ($nurseVisit->image) {
            $this->imageStorageService->deleteImage($nurseVisit->image);
        }

        $nurseVisit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Nurse visit deleted successfully',
        ], 200);
    }
}

