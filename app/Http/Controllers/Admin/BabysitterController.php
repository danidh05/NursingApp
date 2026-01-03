<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Babysitter;
use App\Models\BabysitterAreaPrice;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Babysitters",
 *     description="API Endpoints for managing Babysitters (Admin only)"
 * )
 */
class BabysitterController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $babysitters = Babysitter::all();
        
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
            'day_shift_price_12_hours' => 'required|numeric|min:0',
            'day_shift_price_24_hours' => 'nullable|numeric|min:0', // Deprecated: kept for backward compatibility
            'night_shift_price_12_hours' => 'required|numeric|min:0',
            'night_shift_price_24_hours' => 'nullable|numeric|min:0', // Deprecated: kept for backward compatibility
            'price_24_hours' => 'nullable|numeric|min:0', // Use this for 24-hour pricing (not day/night specific)
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
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'babysitters');
        }

        $babysitter = Babysitter::create([
            'name' => 'Baby Sitter',
            'image' => $imagePath,
            'day_shift_price_12_hours' => $validated['day_shift_price_12_hours'],
            'day_shift_price_24_hours' => $validated['day_shift_price_24_hours'] ?? null, // Deprecated
            'night_shift_price_12_hours' => $validated['night_shift_price_12_hours'],
            'night_shift_price_24_hours' => $validated['night_shift_price_24_hours'] ?? null, // Deprecated
            'price_24_hours' => $validated['price_24_hours'] ?? null,
        ]);

        $babysitter->translations()->create([
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
            BabysitterAreaPrice::create([
                'babysitter_id' => $babysitter->id,
                'area_id' => $area->id,
                'day_shift_price_12_hours' => $validated['day_shift_price_12_hours'],
                'day_shift_price_24_hours' => $validated['day_shift_price_24_hours'] ?? null, // Deprecated
                'night_shift_price_12_hours' => $validated['night_shift_price_12_hours'],
                'night_shift_price_24_hours' => $validated['night_shift_price_24_hours'] ?? null, // Deprecated
                'price_24_hours' => $validated['price_24_hours'] ?? null,
            ]);
        }

        $translation = $babysitter->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Babysitter created successfully',
            'data' => [
                'id' => $babysitter->id,
                'name' => $babysitter->name,
                'image' => $babysitter->image_url,
                'day_shift_price_12_hours' => $babysitter->day_shift_price_12_hours,
                'day_shift_price_24_hours' => $babysitter->day_shift_price_24_hours, // Deprecated
                'night_shift_price_12_hours' => $babysitter->night_shift_price_12_hours,
                'night_shift_price_24_hours' => $babysitter->night_shift_price_24_hours, // Deprecated
                'price_24_hours' => $babysitter->price_24_hours,
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 201);
    }

    public function show(Babysitter $babysitter): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $babysitter->translate($locale);

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
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 200);
    }

    public function update(Request $request, Babysitter $babysitter): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'day_shift_price_12_hours' => 'nullable|numeric|min:0',
            'day_shift_price_24_hours' => 'nullable|numeric|min:0', // Deprecated
            'night_shift_price_12_hours' => 'nullable|numeric|min:0',
            'night_shift_price_24_hours' => 'nullable|numeric|min:0', // Deprecated
            'price_24_hours' => 'nullable|numeric|min:0', // Use this for 24-hour pricing (not day/night specific)
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
                $babysitter->image,
                'babysitters'
            );
            $babysitter->image = $imagePath;
        }

        $babysitter->fill($validated);
        $babysitter->save();

        $translation = $babysitter->translations()->where('locale', $locale)->first();
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
            $babysitter->translations()->create([
                'locale' => $locale,
                'about' => $validated['about'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'additional_instructions' => $validated['additional_instructions'] ?? null,
                'service_include' => $validated['service_include'] ?? null,
                'description' => $validated['description'] ?? null,
                'additional_information' => $validated['additional_information'] ?? null,
            ]);
        }

        $translation = $babysitter->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Babysitter updated successfully',
            'data' => [
                'id' => $babysitter->id,
                'name' => $babysitter->name,
                'image' => $babysitter->image_url,
                'day_shift_price_12_hours' => $babysitter->day_shift_price_12_hours,
                'day_shift_price_24_hours' => $babysitter->day_shift_price_24_hours, // Deprecated
                'night_shift_price_12_hours' => $babysitter->night_shift_price_12_hours,
                'night_shift_price_24_hours' => $babysitter->night_shift_price_24_hours, // Deprecated
                'price_24_hours' => $babysitter->price_24_hours,
                'about' => $translation?->about,
                'terms_and_conditions' => $translation?->terms_and_conditions,
                'additional_instructions' => $translation?->additional_instructions,
                'service_include' => $translation?->service_include,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 200);
    }

    public function destroy(Babysitter $babysitter): JsonResponse
    {
        if ($babysitter->image) {
            $this->imageStorageService->deleteImage($babysitter->image);
        }

        $babysitter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Babysitter deleted successfully',
        ], 200);
    }
}

