<?php

namespace App\Http\Controllers;

use App\Models\TestPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Test Packages",
 *     description="API Endpoints for viewing Test Packages (User accessible)"
 * )
 */
class TestPackageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/test-packages",
     *     summary="List all test packages",
     *     description="Retrieve all test packages with tests and translations based on Accept-Language header",
     *     tags={"Test Packages"},
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
     *                 @OA\Property(property="about_test", type="string", nullable=true),
     *                 @OA\Property(property="instructions", type="string", nullable=true),
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
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale();
        $testPackages = TestPackage::with(['translations', 'tests.translations'])->get();
        
        $testPackages = $testPackages->map(function ($package) use ($locale) {
            $translation = $package->translate($locale);
            
            return [
                'id' => $package->id,
                'name' => $translation?->name ?? $package->name,
                'results' => $package->results,
                'price' => $package->price,
                'image' => $package->image_url,
                'show_details' => $package->show_details,
                'about_test' => $translation?->about_test,
                'instructions' => $translation?->instructions,
                'tests' => $package->tests->map(function ($test) use ($locale) {
                    $testTranslation = $test->translate($locale);
                    return [
                        'id' => $test->id,
                        'sample_type' => $test->sample_type,
                        'price' => $test->price,
                        'image' => $test->image_url,
                        'about_test' => $testTranslation?->about_test,
                        'instructions' => $testTranslation?->instructions,
                    ];
                }),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $testPackages,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/test-packages/{id}",
     *     summary="Get a specific test package",
     *     description="Retrieve a specific test package with tests and translations based on Accept-Language header",
     *     tags={"Test Packages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test Package ID",
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
     *                 @OA\Property(property="about_test", type="string", nullable=true),
     *                 @OA\Property(property="instructions", type="string", nullable=true),
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
     *     @OA\Response(response=404, description="Test package not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $locale = app()->getLocale();
        $testPackage = TestPackage::with(['translations', 'tests.translations'])->findOrFail($id);
        
        $translation = $testPackage->translate($locale);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $testPackage->id,
                'name' => $translation?->name ?? $testPackage->name,
                'results' => $testPackage->results,
                'price' => $testPackage->price,
                'image' => $testPackage->image_url,
                'show_details' => $testPackage->show_details,
                'about_test' => $translation?->about_test,
                'instructions' => $translation?->instructions,
                'tests' => $testPackage->tests->map(function ($test) use ($locale) {
                    $testTranslation = $test->translate($locale);
                    return [
                        'id' => $test->id,
                        'sample_type' => $test->sample_type,
                        'price' => $test->price,
                        'image' => $test->image_url,
                        'about_test' => $testTranslation?->about_test,
                        'instructions' => $testTranslation?->instructions,
                    ];
                }),
            ],
        ], 200);
    }
}
