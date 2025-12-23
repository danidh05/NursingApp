<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorCategory;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorCategoryController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $categories = DoctorCategory::with('translations')->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(function ($cat) use ($locale) {
                $t = $cat->translate($locale);
                return [
                    'id' => $cat->id,
                    'name' => $t?->name,
                    'image' => $cat->image_url,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'locale' => 'nullable|string|in:en,ar',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $locale = $validated['locale'] ?? 'en';
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'doctor-categories');
        }
        $cat = DoctorCategory::create(['image' => $imagePath]);
        $cat->translations()->create(['locale' => $locale, 'name' => $validated['name']]);

        return response()->json([
            'success' => true,
            'message' => 'Doctor category created',
            'data' => [
                'id' => $cat->id,
                'name' => $validated['name'],
                'image' => $cat->image_url,
            ],
        ], 201);
    }

    public function show(DoctorCategory $doctorCategory): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $t = $doctorCategory->translate($locale);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctorCategory->id,
                'name' => $t?->name,
                'image' => $doctorCategory->image_url,
            ],
        ]);
    }

    public function update(Request $request, DoctorCategory $doctorCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'locale' => 'nullable|string|in:en,ar',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $locale = $validated['locale'] ?? 'en';
        if ($request->hasFile('image')) {
            $doctorCategory->image = $this->imageStorageService->updateImage($request->file('image'), $doctorCategory->image, 'doctor-categories');
        }
        $doctorCategory->save();

        if (isset($validated['name'])) {
            $tr = $doctorCategory->translations()->where('locale', $locale)->first();
            if ($tr) {
                $tr->update(['name' => $validated['name']]);
            } else {
                $doctorCategory->translations()->create(['locale' => $locale, 'name' => $validated['name']]);
            }
        }

        $t = $doctorCategory->translate($locale);
        return response()->json([
            'success' => true,
            'message' => 'Doctor category updated',
            'data' => [
                'id' => $doctorCategory->id,
                'name' => $t?->name,
                'image' => $doctorCategory->image_url,
            ],
        ]);
    }

    public function destroy(DoctorCategory $doctorCategory): JsonResponse
    {
        if ($doctorCategory->image) {
            $this->imageStorageService->deleteImage($doctorCategory->image);
        }
        $doctorCategory->delete();
        return response()->json(['success' => true, 'message' => 'Doctor category deleted']);
    }
}

