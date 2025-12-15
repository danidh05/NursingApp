<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Tests",
 *     description="API Endpoints for viewing Tests (User accessible)"
 * )
 */
class TestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tests",
     *     summary="List all tests",
     *     description="Retrieve all tests with translations based on Accept-Language header",
     *     tags={"Tests"},
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
     *         description="Tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/..."),
     *                 @OA\Property(property="about_test", type="string", example="Complete blood count test...", nullable=true),
     *                 @OA\Property(property="instructions", type="string", example="Fasting required...", nullable=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale();
        $tests = Test::with('translations')->get();
        
        $tests = $tests->map(function ($test) use ($locale) {
            $translation = $test->translate($locale);
            
            return [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
                'about_test' => $translation?->about_test,
                'instructions' => $translation?->instructions,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $tests,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/tests/{id}",
     *     summary="Get a specific test",
     *     description="Retrieve a specific test with translations based on Accept-Language header",
     *     tags={"Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
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
     *         description="Test retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="sample_type", type="string", example="Blood"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/tests/..."),
     *                 @OA\Property(property="about_test", type="string", example="Complete blood count test...", nullable=true),
     *                 @OA\Property(property="instructions", type="string", example="Fasting required...", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Test not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $locale = app()->getLocale();
        $test = Test::with('translations')->findOrFail($id);
        
        $translation = $test->translate($locale);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $test->id,
                'sample_type' => $test->sample_type,
                'price' => $test->price,
                'image' => $test->image_url,
                'about_test' => $translation?->about_test,
                'instructions' => $translation?->instructions,
            ],
        ], 200);
    }
}
