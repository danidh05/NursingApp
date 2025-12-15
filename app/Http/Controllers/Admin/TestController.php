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
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/..."),
     *                 @OA\Property(property="translations", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="locale", type="string", example="en"),
     *                     @OA\Property(property="about_test", type="string", example="Complete blood count test..."),
     *                     @OA\Property(property="instructions", type="string", example="Fasting required...")
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
        $tests = Test::with('translations')->get();
        
        return response()->json([
            'success' => true,
            'data' => $tests->map(function ($test) {
                return [
                    'id' => $test->id,
                    'sample_type' => $test->sample_type,
                    'price' => $test->price,
                    'image' => $test->image_url,
                    'translations' => $test->translations,
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
     *                 required={"sample_type","price","locale"},
     *                 @OA\Property(property="sample_type", type="string", example="Blood", description="Type of sample (e.g., Blood, Urine)"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00, description="Test price (same for all areas)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Test image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale"),
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
            'locale' => 'required|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

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
            'locale' => $validatedData['locale'],
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
     *                 @OA\Property(property="translations", type="array", @OA\Items(
     *                     @OA\Property(property="locale", type="string", example="en"),
     *                     @OA\Property(property="about_test", type="string"),
     *                     @OA\Property(property="instructions", type="string")
     *                 ))
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
        $test->load('translations');
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
                'translations' => $test->translations,
            ],
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
     *                 required={"locale"},
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=55.00),
     *                 @OA\Property(property="image", type="string", format="binary", description="New test image (optional)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en"),
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
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/...")
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
        $validatedData = $request->validate([
            'sample_type' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'locale' => 'required|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

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

        return response()->json([
            'success' => true,
            'message' => 'Test updated successfully.',
            'data' => [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
            ],
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
