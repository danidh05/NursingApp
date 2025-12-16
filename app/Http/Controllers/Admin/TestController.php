<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

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
     *                 required={"sample_type","price"},
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
            'sample_type' => $validatedData['sample_type'],
            'price' => $validatedData['price'],
            'image' => $imagePath,
        ]);

        // Create translation
        $test->translations()->create([
            'locale' => $locale,
            'about_test' => $request->about_test ?? null,
            'instructions' => $request->instructions ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test created successfully.',
            'data' => [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
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
     *     description="Update an existing test with image upload and translations. Use form-data (multipart/form-data) for file uploads.",
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
        // FIX: Laravel doesn't parse multipart/form-data for PUT requests automatically
        // We need to access the parsed request parameters directly
        // For PUT/PATCH with multipart/form-data, use $request->request (ParameterBag) instead of $request->all()
        
        // FIX: Laravel doesn't parse multipart/form-data for PUT requests
        // We need to manually parse the request body or use a workaround
        // Solution: Parse multipart/form-data manually for PUT requests
        
        $locale = null;
        $formData = [];
        
        // For PUT requests with multipart/form-data, manually parse the request
        if (($request->isMethod('PUT') || $request->isMethod('PATCH')) && 
            str_contains($request->header('Content-Type', ''), 'multipart/form-data')) {
            
            // Try to get from request->request first (sometimes it works)
            $requestParams = $request->request->all();
            
            // If empty, try to parse manually from the underlying Symfony request
            if (empty($requestParams)) {
                // Access the underlying Symfony Request
                $symfonyRequest = $request->instance();
                if ($symfonyRequest instanceof \Symfony\Component\HttpFoundation\Request) {
                    $requestParams = $symfonyRequest->request->all();
                    
                    // If still empty, the form-data wasn't parsed
                    // In this case, we need to manually parse the request content
                    if (empty($requestParams)) {
                        // Parse multipart/form-data manually
                        $content = $request->getContent();
                        $boundary = null;
                        
                        // Extract boundary from Content-Type header
                        $contentType = $request->header('Content-Type', '');
                        if (preg_match('/boundary=(.+)$/i', $contentType, $matches)) {
                            $boundary = '--' . trim($matches[1]);
                            
                            // Parse multipart data
                            $parts = explode($boundary, $content);
                            foreach ($parts as $part) {
                                if (preg_match('/name="([^"]+)"/', $part, $nameMatch)) {
                                    $fieldName = $nameMatch[1];
                                    // Extract value (text after headers, before next boundary)
                                    if (preg_match('/\r\n\r\n(.*?)(?:\r\n--|$)/s', $part, $valueMatch)) {
                                        $value = trim($valueMatch[1]);
                                        // Skip file uploads (they have Content-Type in headers)
                                        if (!str_contains($part, 'Content-Type:')) {
                                            $formData[$fieldName] = $value;
                                        }
                                    }
                                }
                            }
                            
                            // Merge parsed data into request
                            if (!empty($formData)) {
                                $request->merge($formData);
                                $requestParams = $formData;
                            }
                        }
                    } else {
                        // Merge the parsed parameters
                        $request->merge($requestParams);
                    }
                }
            } else {
                // Parameters were found, merge them
                $request->merge($requestParams);
            }
        }
        
        // Now get locale from the merged request, default to 'en' if not provided
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
            'sample_type' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);
        
        // Add locale to validated data
        $validatedData['locale'] = $locale;

        // Update image if provided
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
