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
     *                 @OA\Property(property="translations", type="array", @OA\Items()),
     *                 @OA\Property(property="tests", type="array", @OA\Items())
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        $testPackages = TestPackage::with(['translations', 'tests'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $testPackages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'results' => $package->results,
                    'price' => $package->price,
                    'image' => $package->image_url,
                    'show_details' => $package->show_details,
                    'translations' => $package->translations,
                    'tests' => $package->tests,
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
     *                 required={"name","results","price","locale","test_ids"},
     *                 @OA\Property(property="name", type="string", example="Basic Package", description="Package name"),
     *                 @OA\Property(property="results", type="string", example="within 48 hours", description="Results timeframe"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00, description="Package price (same for all areas)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Package image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="show_details", type="boolean", example=true, description="Show details flag"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale"),
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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'results' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'show_details' => 'nullable|boolean',
            'locale' => 'required|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
            'test_ids' => 'required|array|min:1',
            'test_ids.*' => 'exists:tests,id',
        ]);

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
            'locale' => $validatedData['locale'],
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
     *                 @OA\Property(property="translations", type="array", @OA\Items()),
     *                 @OA\Property(property="tests", type="array", @OA\Items())
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
        $testPackage->load(['translations', 'tests']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $testPackage->id,
                'name' => $testPackage->name,
                'results' => $testPackage->results,
                'price' => $testPackage->price,
                'image' => $testPackage->image_url,
                'show_details' => $testPackage->show_details,
                'translations' => $testPackage->translations,
                'tests' => $testPackage->tests,
            ],
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/test-packages/{id}",
     *     summary="Update a test package (Admin)",
     *     description="Update an existing test package with image, translations, and test relationships. Use form-data (multipart/form-data) for file uploads.",
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
     *                 required={"locale"},
     *                 @OA\Property(property="name", type="string", example="Basic Package Updated"),
     *                 @OA\Property(property="results", type="string", example="within 48 hours"),
     *                 @OA\Property(property="price", type="number", format="float", example=160.00),
     *                 @OA\Property(property="image", type="string", format="binary", description="New package image (optional)"),
     *                 @OA\Property(property="show_details", type="boolean", example=true),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en"),
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
     *                 @OA\Property(property="price", type="number", format="float", example=160.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/test-packages/..."),
     *                 @OA\Property(property="tests", type="array", @OA\Items())
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
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'results' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'show_details' => 'sometimes|boolean',
            'locale' => 'required|string|in:en,ar',
            'about_test' => 'nullable|string',
            'instructions' => 'nullable|string',
            'test_ids' => 'sometimes|array|min:1',
            'test_ids.*' => 'exists:tests,id',
        ]);

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
            ['locale' => $validatedData['locale']],
            [
                'name' => $validatedData['name'] ?? $testPackage->name,
                'about_test' => $request->about_test ?? null,
                'instructions' => $request->instructions ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Test package updated successfully.',
            'data' => [
                'id' => $testPackage->id,
                'name' => $testPackage->name,
                'price' => $testPackage->price,
                'image' => $testPackage->image_url,
                'tests' => $testPackage->tests,
            ],
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
}
