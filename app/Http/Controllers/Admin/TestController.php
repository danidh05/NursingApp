<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Tag(
 *     name="Admin - Tests",
 *     description="API Endpoints for Test management (Admin only)"
 * )
 */
class TestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImageStorageService $imageStorageService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/tests",
     *     summary="List all tests (Admin)",
     *     description="Retrieve all tests with translations for admin management",
     *     tags={"Admin - Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Complete Blood Count", description="Test name (translatable)"),
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/..."),
     *                 @OA\Property(property="about_test", type="string", example="Complete blood count test...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", example="Fasting required...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
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
        
        $tests = Test::all();
        
        return response()->json([
            'success' => true,
            'data' => $tests->map(function ($test) use ($locale) {
                $translation = $test->translate($locale);
                return [
                    'id' => $test->id,
                    'name' => $translation ? $translation->name : $test->name,
                    'sample_type' => $test->sample_type,
                    'price' => $test->price,
                    'image' => $test->image_url,
                    'about_test' => $translation ? $translation->about_test : null,
                    'instructions' => $translation ? $translation->instructions : null,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/tests",
     *     summary="Create a new test (Admin)",
     *     description="Create a new test with image upload and translations. Use form-data (multipart/form-data) for file uploads.",
     *     tags={"Admin - Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
         *             @OA\Schema(
         *                 required={"name","sample_type","price"},
         *                 @OA\Property(property="name", type="string", example="Complete Blood Count", description="Test name (translatable)"),
         *                 @OA\Property(property="sample_type", type="string", example="Blood", description="Type of sample (e.g., Blood, Urine)"),
         *                 @OA\Property(property="price", type="number", format="float", example=50.00, description="Test price (same for all areas)"),
         *                 @OA\Property(property="image", type="string", format="binary", description="Test image file (jpg, png, max 2MB)"),
         *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
         *                 @OA\Property(property="about_test", type="string", example="Complete blood count test description", description="About test description (translatable)"),
         *                 @OA\Property(property="instructions", type="string", example="Fasting required for 8 hours", description="Test instructions (translatable)")
         *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/...")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'sample_type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'locale' => 'nullable|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        // Default locale to 'en' if not provided
        $locale = $validatedData['locale'] ?? 'en';

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'tests');
        }

        // Create test
        $test = Test::create([
            'name' => $validatedData['name'],
            'sample_type' => $validatedData['sample_type'],
            'price' => $validatedData['price'],
            'image' => $imagePath,
        ]);

        // Create translation
        $test->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'about_test' => $request->about_test ?? null,
            'instructions' => $request->instructions ?? null,
        ]);

        // Get translation for response
        $translation = $test->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Test created successfully.',
            'data' => [
                'id' => $test->id,
                'name' => $translation ? $translation->name : $test->name,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
                'about_test' => $translation?->about_test,
                'instructions' => $translation?->instructions,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/tests/{id}",
     *     summary="Get a specific test (Admin)",
     *     description="Retrieve a specific test with translations",
     *     tags={"Admin - Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Complete Blood Count", description="Test name (translatable)"),
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/..."),
     *                 @OA\Property(property="about_test", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Test not found")
     * )
     */
    public function show(Test $test): JsonResponse
    {
        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $test->translate($locale);
        
        $data = [
            'id' => $test->id,
            'sample_type' => $test->sample_type,
            'price' => $test->price,
            'image' => $test->image_url,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['about_test'] = $translation->about_test;
            $data['instructions'] = $translation->instructions;
        } else {
            $data['about_test'] = null;
            $data['instructions'] = null;
        }
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

        /**
         * @OA\Put(
         *     path="/api/admin/tests/{id}",
         *     summary="Update a test (Admin)",
         *     description="Update an existing test with image upload and translations. **CRITICAL FOR FILE UPLOADS:** This endpoint accepts both PUT (for non-file updates) and POST with `_method=PUT` (for file uploads). When uploading files, you MUST: 1) Use POST method (not PUT), 2) Include `_method=PUT` in form-data, 3) Use multipart/form-data. This is Laravel's method spoofing - required because PHP only populates \$_FILES for POST requests.",
     *     tags={"Admin - Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
         *             @OA\Schema(
         *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method. This enables Laravel method spoofing. Omit this field if using actual PUT request (without file uploads)."),
         *                 @OA\Property(property="name", type="string", example="Complete Blood Count - Updated", description="Test name (translatable)"),
         *                 @OA\Property(property="sample_type", type="string", example="Blood"),
         *                 @OA\Property(property="price", type="number", format="float", example=55.00),
         *                 @OA\Property(property="image", type="string", format="binary", description="New test image (optional)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
         *                 @OA\Property(property="about_test", type="string", example="Updated description"),
         *                 @OA\Property(property="instructions", type="string", example="Updated instructions")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test updated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=55.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/..."),
     *                 @OA\Property(property="about_test", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Test $test): JsonResponse
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
            'sample_type' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);
        
        // Add locale to validated data
        $validatedData['locale'] = $locale;

        // Update image if provided
        // After parseMultipartFormDataForPut, files should be accessible via hasFile()
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $test->image,
                'tests'
            );
            $test->image = $imagePath;
        }

        // Update test fields
        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'sample_type' => $validatedData['sample_type'] ?? null,
            'price' => $validatedData['price'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $test->update($updateData);
        }

        // Update or create translation
        $test->translations()->updateOrCreate(
            ['locale' => $validatedData['locale']],
            [
                'name' => $validatedData['name'] ?? $test->name,
                'about_test' => $request->about_test ?? null,
                'instructions' => $request->instructions ?? null,
            ]
        );

        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $test->translate($locale);
        
        $data = [
            'id' => $test->id,
            'sample_type' => $test->sample_type,
            'price' => $test->price,
            'image' => $test->image_url,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['about_test'] = $translation->about_test;
            $data['instructions'] = $translation->instructions;
        } else {
            $data['about_test'] = null;
            $data['instructions'] = null;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Test updated successfully.',
            'data' => $data,
        ], 200);
    }


    /**
     * @OA\Delete(
     *     path="/api/admin/tests/{id}",
     *     summary="Delete a test (Admin)",
     *     description="Delete a test and its associated image and translations",
     *     tags={"Admin - Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Test not found")
     * )
     */
    public function destroy(Test $test): JsonResponse
    {
        // Delete image if exists
        if ($test->image) {
            $this->imageStorageService->deleteImage($test->image);
        }

        $test->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test deleted successfully.',
        ], 200);
    }
}
