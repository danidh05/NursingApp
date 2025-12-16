<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestPackage;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Tag(
 *     name="Admin - Test Packages",
 *     description="API Endpoints for Test Package management (Admin only)"
 * )
 */
class TestPackageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImageStorageService $imageStorageService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/test-packages",
     *     summary="List all test packages (Admin)",
     *     description="Retrieve all test packages with tests and translations for admin management",
     *     tags={"Admin - Test Packages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Test packages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Basic Package"),
     *                 @OA\Property(property="results", type="string", example="within 48 hours"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/test-packages/..."),
     *                 @OA\Property(property="show_details", type="boolean", example=true),
     *                 @OA\Property(property="about_test", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="tests", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="sample_type", type="string", example="Blood"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="about_test", type="string", nullable=true),
     *                     @OA\Property(property="instructions", type="string", nullable=true)
     *                 ))
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
        
        $testPackages = TestPackage::with('tests')->get();
        
        return response()->json([
            'success' => true,
            'data' => $testPackages->map(function ($package) use ($locale) {
                $translation = $package->translate($locale);
                return [
                    'id' => $package->id,
                    'name' => $translation ? $translation->name : $package->name, // Use translated name or fallback
                    'results' => $package->results,
                    'price' => $package->price,
                    'image' => $package->image_url,
                    'show_details' => $package->show_details,
                    'about_test' => $translation ? $translation->about_test : null,
                    'instructions' => $translation ? $translation->instructions : null,
                    'tests' => $package->tests->map(function ($test) use ($locale) {
                        $testTranslation = $test->translate($locale);
                        return [
                            'id' => $test->id,
                            'sample_type' => $test->sample_type,
                            'price' => $test->price,
                            'image' => $test->image_url,
                            'about_test' => $testTranslation ? $testTranslation->about_test : null,
                            'instructions' => $testTranslation ? $testTranslation->instructions : null,
                        ];
                    }),
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/test-packages",
     *     summary="Create a new test package (Admin)",
     *     description="Create a new test package with image, translations, and associated tests. Use form-data (multipart/form-data) for file uploads.",
     *     tags={"Admin - Test Packages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","results","price","test_ids"},
     *                 @OA\Property(property="name", type="string", example="Basic Package", description="Package name"),
     *                 @OA\Property(property="results", type="string", example="within 48 hours", description="Results timeframe"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00, description="Package price (same for all areas)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Package image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="show_details", type="boolean", example=true, description="Show details flag"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="about_test", type="string", example="Basic health screening package", description="About test (translatable)"),
     *                 @OA\Property(property="instructions", type="string", example="Follow all test instructions", description="Instructions (translatable)"),
     *                 @OA\Property(property="test_ids", type="array", @OA\Items(type="integer"), example={1,2}, description="Array of test IDs to include in package")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Test package created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test package created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Basic Package"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/test-packages/..."),
     *                 @OA\Property(property="tests", type="array", @OA\Items())
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
        // Normalize form-data inputs before validation
        $normalized = $this->normalizeFormData($request);
        $request->merge($normalized);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'results' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'show_details' => 'nullable|boolean',
            'locale' => 'nullable|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
            'test_ids' => 'required|array|min:1',
            'test_ids.*' => 'exists:tests,id',
        ]);

        // Default locale to 'en' if not provided
        $locale = $validatedData['locale'] ?? 'en';

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'test-packages');
        }

        // Create test package
        $testPackage = TestPackage::create([
            'name' => $validatedData['name'],
            'results' => $validatedData['results'],
            'price' => $validatedData['price'],
            'image' => $imagePath,
            'show_details' => $validatedData['show_details'] ?? true,
        ]);

        // Attach tests
        $testPackage->tests()->attach($validatedData['test_ids']);

        // Create translation
        $testPackage->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'about_test' => $request->about_test ?? null,
            'instructions' => $request->instructions ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test package created successfully.',
            'data' => [
                'id' => $testPackage->id,
                'name' => $testPackage->name,
                'price' => $testPackage->price,
                'image' => $testPackage->image_url,
                'tests' => $testPackage->tests,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/test-packages/{id}",
     *     summary="Get a specific test package (Admin)",
     *     description="Retrieve a specific test package with tests and translations",
     *     tags={"Admin - Test Packages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test Package ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test package retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Basic Package"),
     *                 @OA\Property(property="results", type="string", example="within 48 hours"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/test-packages/..."),
     *                 @OA\Property(property="show_details", type="boolean", example=true),
     *                 @OA\Property(property="about_test", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="tests", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="sample_type", type="string", example="Blood"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="about_test", type="string", nullable=true),
     *                     @OA\Property(property="instructions", type="string", nullable=true)
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Test package not found")
     * )
     */
    public function show(TestPackage $testPackage): JsonResponse
    {
        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $testPackage->translate($locale);
        
        // Load tests with their translations
        $testPackage->load('tests');
        
        $data = [
            'id' => $testPackage->id,
            'name' => $translation ? $translation->name : $testPackage->name, // Use translated name or fallback to base name
            'results' => $testPackage->results,
            'price' => $testPackage->price,
            'image' => $testPackage->image_url,
            'show_details' => $testPackage->show_details,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['about_test'] = $translation->about_test;
            $data['instructions'] = $translation->instructions;
        } else {
            $data['about_test'] = null;
            $data['instructions'] = null;
        }
        
        // Format tests with single locale translation data
        $data['tests'] = $testPackage->tests->map(function ($test) use ($locale) {
            $testTranslation = $test->translate($locale);
            return [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
                'about_test' => $testTranslation ? $testTranslation->about_test : null,
                'instructions' => $testTranslation ? $testTranslation->instructions : null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

        /**
         * @OA\Put(
         *     path="/api/admin/test-packages/{id}",
         *     summary="Update a test package (Admin)",
         *     description="Update an existing test package with image, translations, and test relationships. **CRITICAL FOR FILE UPLOADS:** This endpoint accepts both PUT (for non-file updates) and POST with `_method=PUT` (for file uploads). When uploading files, you MUST: 1) Use POST method (not PUT), 2) Include `_method=PUT` in form-data, 3) Use multipart/form-data. This is Laravel's method spoofing - required because PHP only populates \$_FILES for POST requests.",
     *     tags={"Admin - Test Packages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test Package ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
         *             @OA\Schema(
         *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method. This enables Laravel method spoofing. Omit this field if using actual PUT request (without file uploads)."),
         *                 @OA\Property(property="name", type="string", example="Basic Package Updated"),
         *                 @OA\Property(property="results", type="string", example="within 48 hours"),
         *                 @OA\Property(property="price", type="number", format="float", example=160.00),
         *                 @OA\Property(property="image", type="string", format="binary", description="New package image (optional)"),
     *                 @OA\Property(property="show_details", type="boolean", example=true),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="about_test", type="string", example="Updated description"),
     *                 @OA\Property(property="instructions", type="string", example="Updated instructions"),
     *                 @OA\Property(property="test_ids", type="array", @OA\Items(type="integer"), example={1,2,3}, description="Updated array of test IDs")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test package updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test package updated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Basic Package Updated"),
     *                 @OA\Property(property="results", type="string", example="within 48 hours"),
     *                 @OA\Property(property="price", type="number", format="float", example=160.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/test-packages/..."),
     *                 @OA\Property(property="show_details", type="boolean", example=true),
     *                 @OA\Property(property="about_test", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="tests", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="sample_type", type="string", example="Blood"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="about_test", type="string", nullable=true),
     *                     @OA\Property(property="instructions", type="string", nullable=true)
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Test package not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, TestPackage $testPackage): JsonResponse
    {
        // Normalize form-data inputs before validation
        $normalized = $this->normalizeFormData($request);
        $request->merge($normalized);
        
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'results' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'show_details' => 'sometimes|boolean',
            'locale' => 'nullable|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
            'test_ids' => 'sometimes|array|min:1',
            'test_ids.*' => 'exists:tests,id',
        ]);

        // Default locale to 'en' if not provided
        $locale = $validatedData['locale'] ?? 'en';

        // Update image if provided
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $testPackage->image,
                'test-packages'
            );
            $testPackage->image = $imagePath;
        }

        // Update test package fields
        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'results' => $validatedData['results'] ?? null,
            'price' => $validatedData['price'] ?? null,
            'show_details' => $validatedData['show_details'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $testPackage->update($updateData);
        }

        // Update test relationships if provided
        if (isset($validatedData['test_ids'])) {
            $testPackage->tests()->sync($validatedData['test_ids']);
        }

        // Update or create translation
        $testPackage->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'name' => $validatedData['name'] ?? $testPackage->name,
                'about_test' => $request->about_test ?? null,
                'instructions' => $request->instructions ?? null,
            ]
        );

        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $testPackage->translate($locale);
        
        // Load tests
        $testPackage->load('tests');
        
        $data = [
            'id' => $testPackage->id,
            'name' => $translation ? $translation->name : $testPackage->name, // Use translated name or fallback to base name
            'results' => $testPackage->results,
            'price' => $testPackage->price,
            'image' => $testPackage->image_url,
            'show_details' => $testPackage->show_details,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['about_test'] = $translation->about_test;
            $data['instructions'] = $translation->instructions;
        } else {
            $data['about_test'] = null;
            $data['instructions'] = null;
        }
        
        // Format tests with single locale translation data
        $data['tests'] = $testPackage->tests->map(function ($test) use ($locale) {
            $testTranslation = $test->translate($locale);
            return [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
                'about_test' => $testTranslation ? $testTranslation->about_test : null,
                'instructions' => $testTranslation ? $testTranslation->instructions : null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Test package updated successfully.',
            'data' => $data,
        ], 200);
    }


    /**
     * @OA\Delete(
     *     path="/api/admin/test-packages/{id}",
     *     summary="Delete a test package (Admin)",
     *     description="Delete a test package and its associated image and translations",
     *     tags={"Admin - Test Packages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test Package ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test package deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test package deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Test package not found")
     * )
     */
    public function destroy(TestPackage $testPackage): JsonResponse
    {
        // Delete image if exists
        if ($testPackage->image) {
            $this->imageStorageService->deleteImage($testPackage->image);
        }

        $testPackage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test package deleted successfully.',
        ], 200);
    }

    /**
     * Normalize form-data inputs for validation.
     * Handles:
     * - Boolean strings (true/false/1/0) -> boolean
     * - Array strings ([1,2,3] or "1,2,3") -> array
     *
     * @param Request $request
     * @return array
     */
    private function normalizeFormData(Request $request): array
    {
        $normalized = [];
        
        // Normalize show_details: accepts true/false/1/0/on/yes/no
        if ($request->has('show_details')) {
            $value = $request->input('show_details');
            $normalized['show_details'] = $this->normalizeBoolean($value);
        }
        
        // Normalize test_ids: handles multiple formats
        if ($request->has('test_ids')) {
            $value = $request->input('test_ids');
            $normalized['test_ids'] = $this->normalizeArray($value);
        }
        
        return $normalized;
    }
    
    /**
     * Normalize boolean value from form-data.
     * Accepts: true, false, "true", "false", "1", "0", "on", "yes", "no"
     *
     * @param mixed $value
     * @return bool|null
     */
    private function normalizeBoolean($value): ?bool
    {
        if ($value === null) {
            return null;
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'on', 'yes'], true);
        }
        
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        return false;
    }
    
    /**
     * Normalize array value from form-data.
     * Handles:
     * - JSON string: "[1,2,3]" -> [1,2,3]
     * - Comma-separated: "1,2,3" -> [1,2,3]
     * - Already array: [1,2,3] -> [1,2,3]
     * - Multiple form fields with same name: test_ids[] -> [1,2,3]
     *
     * @param mixed $value
     * @return array
     */
    private function normalizeArray($value): array
    {
        // Already an array
        if (is_array($value)) {
            // Filter out empty values and convert to integers
            return array_filter(array_map('intval', $value), fn($v) => $v > 0);
        }
        
        // JSON string like "[1,2,3]"
        if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_filter(array_map('intval', $decoded), fn($v) => $v > 0);
            }
        }
        
        // Comma-separated string like "1,2,3"
        if (is_string($value) && str_contains($value, ',')) {
            $parts = explode(',', $value);
            return array_filter(array_map(function($part) {
                return intval(trim($part));
            }, $parts), fn($v) => $v > 0);
        }
        
        // Single value - convert to array
        if (is_numeric($value)) {
            $intValue = intval($value);
            return $intValue > 0 ? [$intValue] : [];
        }
        
        return [];
    }
}
