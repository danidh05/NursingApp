<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ray;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Rays",
 *     description="API Endpoints for managing Rays (Admin only)"
 * )
 */
class RayController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/rays",
     *     summary="List all rays",
     *     description="Retrieve all rays with translations based on Accept-Language header",
     *     tags={"Admin - Rays"},
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
     *         description="Rays retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/..."),
     *                 @OA\Property(property="about_ray", type="string", example="Chest X-Ray description...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", example="Follow instructions...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="additional_information", type="string", example="Additional info...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        $rays = Ray::all();
        
        return response()->json([
            'success' => true,
            'data' => $rays->map(function ($ray) use ($locale) {
                $translation = $ray->translate($locale);
                return [
                    'id' => $ray->id,
                    'name' => $translation ? $translation->name : $ray->name,
                    'price' => $ray->price,
                    'image' => $ray->image_url,
                    'about_ray' => $translation ? $translation->about_ray : null,
                    'instructions' => $translation ? $translation->instructions : null,
                    'additional_information' => $translation ? $translation->additional_information : null,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/rays",
     *     summary="Create a new ray",
     *     description="Create a new ray with translations. Supports multipart/form-data for image upload.",
     *     tags={"Admin - Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","price"},
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00, description="Ray price (same for all areas)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Ray image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="about_ray", type="string", example="Chest X-Ray description", description="About ray description (translatable)"),
     *                 @OA\Property(property="instructions", type="string", example="Follow all instructions carefully", description="Ray instructions (translatable)"),
     *                 @OA\Property(property="additional_information", type="string", example="Additional information", description="Additional information (translatable)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ray created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ray created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     *     )
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'locale' => 'nullable|string|in:en,ar',
            'about_ray' => 'nullable|string',
            'instructions' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        // Default locale to 'en' if not provided
        $locale = $validatedData['locale'] ?? 'en';

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'rays');
        }

        // Create ray
        $ray = Ray::create([
            'name' => $validatedData['name'],
            'price' => $validatedData['price'],
            'image' => $imagePath,
        ]);

        // Create translation
        $ray->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'about_ray' => $request->about_ray ?? null,
            'instructions' => $request->instructions ?? null,
            'additional_information' => $request->additional_information ?? null,
        ]);

        // Get translation for response
        $translation = $ray->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Ray created successfully.',
            'data' => [
                'id' => $ray->id,
                'name' => $translation ? $translation->name : $ray->name,
                'price' => $ray->price,
                'image' => $ray->image_url,
                'about_ray' => $translation?->about_ray,
                'instructions' => $translation?->instructions,
                'additional_information' => $translation?->additional_information,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/rays/{id}",
     *     summary="Get a specific ray",
     *     description="Retrieve a specific ray with translations based on Accept-Language header",
     *     tags={"Admin - Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ray ID",
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
     *         description="Ray retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/..."),
     *                 @OA\Property(property="about_ray", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Ray not found")
     * )
     */
    public function show(Ray $ray): JsonResponse
    {
        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $ray->translate($locale);
        
        $data = [
            'id' => $ray->id,
            'name' => $translation ? $translation->name : $ray->name,
            'price' => $ray->price,
            'image' => $ray->image_url,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['about_ray'] = $translation->about_ray;
            $data['instructions'] = $translation->instructions;
            $data['additional_information'] = $translation->additional_information;
        } else {
            $data['about_ray'] = null;
            $data['instructions'] = null;
            $data['additional_information'] = null;
        }
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/rays/{id}",
     *     summary="Update a ray",
     *     description="Update an existing ray. Use POST with _method=PUT for file uploads. Supports multipart/form-data.",
     *     tags={"Admin - Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ray ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method. This enables Laravel method spoofing. Omit this field if using actual PUT request (without file uploads)."),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray - Updated", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=110.00),
     *                 @OA\Property(property="image", type="string", format="binary", description="New ray image (optional)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="about_ray", type="string", example="Updated description"),
     *                 @OA\Property(property="instructions", type="string", example="Updated instructions"),
     *                 @OA\Property(property="additional_information", type="string", example="Updated additional information")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ray updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ray updated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chest X-Ray - Updated", description="Ray name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=110.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/rays/..."),
     *                 @OA\Property(property="about_ray", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Ray not found")
     * )
     */
    public function update(Request $request, Ray $ray): JsonResponse
    {
        // Get locale from request, default to 'en' if not provided
        $locale = $request->input('locale');
        
        // Trim whitespace if it's a string
        if (is_string($locale)) {
            $locale = trim($locale);
        }
        
        // Default to 'en' if locale is not provided or invalid
        if (!$locale || !in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'about_ray' => 'nullable|string',
            'instructions' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);
        
        // Add locale to validated data
        $validatedData['locale'] = $locale;

        // Update image if provided
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $ray->image,
                'rays'
            );
            $ray->image = $imagePath;
        }

        // Update ray fields
        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'price' => $validatedData['price'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $ray->update($updateData);
        }

        // Update or create translation
        $ray->translations()->updateOrCreate(
            ['locale' => $validatedData['locale']],
            [
                'name' => $validatedData['name'] ?? $ray->name,
                'about_ray' => $request->about_ray ?? null,
                'instructions' => $request->instructions ?? null,
                'additional_information' => $request->additional_information ?? null,
            ]
        );

        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $ray->translate($locale);
        
        $data = [
            'id' => $ray->id,
            'name' => $translation ? $translation->name : $ray->name,
            'price' => $ray->price,
            'image' => $ray->image_url,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['about_ray'] = $translation->about_ray;
            $data['instructions'] = $translation->instructions;
            $data['additional_information'] = $translation->additional_information;
        } else {
            $data['about_ray'] = null;
            $data['instructions'] = null;
            $data['additional_information'] = null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Ray updated successfully.',
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/rays/{id}",
     *     summary="Delete a ray",
     *     description="Delete a ray (soft delete if supported, otherwise hard delete)",
     *     tags={"Admin - Rays"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ray ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ray deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ray deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Ray not found")
     * )
     */
    public function destroy(Ray $ray): JsonResponse
    {
        // Delete image if exists
        if ($ray->image) {
            $this->imageStorageService->deleteImage($ray->image);
        }

        $ray->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ray deleted successfully.',
        ], 200);
    }
}

