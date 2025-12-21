<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Duty;
use App\Models\DutyAreaPrice;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Duties",
 *     description="API Endpoints for managing Duties (Admin only)"
 * )
 */
class DutyController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $duties = Duty::all();
        
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'day_shift_price_4_hours' => 'required|numeric|min:0',
            'day_shift_price_6_hours' => 'required|numeric|min:0',
            'day_shift_price_8_hours' => 'required|numeric|min:0',
            'day_shift_price_12_hours' => 'required|numeric|min:0',
            'night_shift_price_4_hours' => 'required|numeric|min:0',
            'night_shift_price_6_hours' => 'required|numeric|min:0',
            'night_shift_price_8_hours' => 'required|numeric|min:0',
            'night_shift_price_12_hours' => 'required|numeric|min:0',
            'continuous_care_price' => 'required|numeric|min:0',
            'locale' => 'nullable|string|in:en,ar',
            'about' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'additional_instructions' => 'nullable|string',
            'service_include' => 'nullable|string',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        $locale = $validated['locale'] ?? 'en';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'duties');
        }

        $duty = Duty::create([
            'name' => 'Duty',
            'image' => $imagePath,
            'day_shift_price_4_hours' => $validated['day_shift_price_4_hours'],
            'day_shift_price_6_hours' => $validated['day_shift_price_6_hours'],
            'day_shift_price_8_hours' => $validated['day_shift_price_8_hours'],
            'day_shift_price_12_hours' => $validated['day_shift_price_12_hours'],
            'night_shift_price_4_hours' => $validated['night_shift_price_4_hours'],
            'night_shift_price_6_hours' => $validated['night_shift_price_6_hours'],
            'night_shift_price_8_hours' => $validated['night_shift_price_8_hours'],
            'night_shift_price_12_hours' => $validated['night_shift_price_12_hours'],
            'continuous_care_price' => $validated['continuous_care_price'],
        ]);

        $duty->translations()->create([
            'locale' => $locale,
            'about' => $validated['about'] ?? null,
            'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            'additional_instructions' => $validated['additional_instructions'] ?? null,
            'service_include' => $validated['service_include'] ?? null,
            'description' => $validated['description'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        // Create area prices for all areas
        $areas = Area::all();
        foreach ($areas as $area) {
            DutyAreaPrice::create([
                'duty_id' => $duty->id,
                'area_id' => $area->id,
                'day_shift_price_4_hours' => $validated['day_shift_price_4_hours'],
                'day_shift_price_6_hours' => $validated['day_shift_price_6_hours'],
                'day_shift_price_8_hours' => $validated['day_shift_price_8_hours'],
                'day_shift_price_12_hours' => $validated['day_shift_price_12_hours'],
                'night_shift_price_4_hours' => $validated['night_shift_price_4_hours'],
                'night_shift_price_6_hours' => $validated['night_shift_price_6_hours'],
                'night_shift_price_8_hours' => $validated['night_shift_price_8_hours'],
                'night_shift_price_12_hours' => $validated['night_shift_price_12_hours'],
                'continuous_care_price' => $validated['continuous_care_price'],
            ]);
        }

        $translation = $duty->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Duty created successfully',
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
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 201);
    }

    public function show(Duty $duty): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $duty->translate($locale);

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
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 200);
    }

    public function update(Request $request, Duty $duty): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'day_shift_price_4_hours' => 'nullable|numeric|min:0',
            'day_shift_price_6_hours' => 'nullable|numeric|min:0',
            'day_shift_price_8_hours' => 'nullable|numeric|min:0',
            'day_shift_price_12_hours' => 'nullable|numeric|min:0',
            'night_shift_price_4_hours' => 'nullable|numeric|min:0',
            'night_shift_price_6_hours' => 'nullable|numeric|min:0',
            'night_shift_price_8_hours' => 'nullable|numeric|min:0',
            'night_shift_price_12_hours' => 'nullable|numeric|min:0',
            'continuous_care_price' => 'nullable|numeric|min:0',
            'locale' => 'nullable|string|in:en,ar',
            'about' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'additional_instructions' => 'nullable|string',
            'service_include' => 'nullable|string',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        $locale = $validated['locale'] ?? 'en';

        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $duty->image,
                'duties'
            );
            $duty->image = $imagePath;
        }

        $duty->fill($validated);
        $duty->save();

        $translation = $duty->translations()->where('locale', $locale)->first();
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
            $duty->translations()->create([
                'locale' => $locale,
                'about' => $validated['about'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'additional_instructions' => $validated['additional_instructions'] ?? null,
                'service_include' => $validated['service_include'] ?? null,
                'description' => $validated['description'] ?? null,
                'additional_information' => $validated['additional_information'] ?? null,
            ]);
        }

        $translation = $duty->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Duty updated successfully',
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
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 200);
    }

    public function destroy(Duty $duty): JsonResponse
    {
        if ($duty->image) {
            $this->imageStorageService->deleteImage($duty->image);
        }

        $duty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Duty deleted successfully',
        ], 200);
    }
}

