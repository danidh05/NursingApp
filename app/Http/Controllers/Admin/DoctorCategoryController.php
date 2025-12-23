<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorCategory;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Doctor Categories",
 *     description="API Endpoints for managing Doctor Categories (Admin only)"
 * )
 */
class DoctorCategoryController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/doctor-categories",
     *     summary="List all doctor categories",
     *     tags={"Admin - Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/admin/doctor-categories",
     *     summary="Create a new doctor category",
     *     tags={"Admin - Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Cardiology", description="Category name (translatable)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Category image (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/admin/doctor-categories/{id}",
     *     summary="Get doctor category details",
     *     tags={"Admin - Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/admin/doctor-categories/{id}",
     *     summary="Update doctor category",
     *     description="Update a doctor category. Use POST with _method=PUT for file uploads. All fields are optional.",
     *     tags={"Admin - Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="name", type="string", example="Cardiology", description="Category name (translatable)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Category image (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/admin/doctor-categories/{id}",
     *     summary="Delete doctor category",
     *     tags={"Admin - Doctor Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy(DoctorCategory $doctorCategory): JsonResponse
    {
        if ($doctorCategory->image) {
            $this->imageStorageService->deleteImage($doctorCategory->image);
        }
        $doctorCategory->delete();
        return response()->json(['success' => true, 'message' => 'Doctor category deleted']);
    }
}

