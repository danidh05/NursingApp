<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Physiotherapist;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Physiotherapists",
 *     description="API Endpoints for managing Physiotherapists (Admin only)"
 * )
 */
class PhysiotherapistController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/physiotherapists",
     *     summary="List all physiotherapists",
     *     description="Retrieve all physiotherapists with translations based on Accept-Language header",
     *     tags={"Admin - Physiotherapists"},
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
     *         description="Physiotherapists retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith", description="Physiotherapist name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/physiotherapists/..."),
     *                 @OA\Property(property="job_name", type="string", example="Senior Physiotherapist"),
     *                 @OA\Property(property="job_specification", type="string", example="Musculoskeletal Specialist"),
     *                 @OA\Property(property="specialization", type="string", example="Sports Medicine"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=10),
     *                 @OA\Property(property="description", type="string", example="Experienced physiotherapist...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $physiotherapists = Physiotherapist::all();
        
        return response()->json([
            'success' => true,
            'data' => $physiotherapists->map(function ($physiotherapist) use ($locale) {
                $translation = $physiotherapist->translate($locale);
                return [
                    'id' => $physiotherapist->id,
                    'name' => $translation ? $translation->name : $physiotherapist->name,
                    'price' => $physiotherapist->price,
                    'image' => $physiotherapist->image_url,
                    'job_name' => $physiotherapist->job_name,
                    'job_specification' => $physiotherapist->job_specification,
                    'specialization' => $physiotherapist->specialization,
                    'years_of_experience' => $physiotherapist->years_of_experience,
                    'description' => $translation ? $translation->description : null,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/physiotherapists",
     *     summary="Create a new physiotherapist",
     *     description="Create a new physiotherapist with translations. Supports multipart/form-data for image upload.",
     *     tags={"Admin - Physiotherapists"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","price"},
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith", description="Physiotherapist name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00, description="Base price (same for all areas initially)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Physiotherapist image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="job_name", type="string", example="Senior Physiotherapist", description="Job name/title"),
     *                 @OA\Property(property="job_specification", type="string", example="Musculoskeletal Specialist", description="Job specification"),
     *                 @OA\Property(property="specialization", type="string", example="Sports Medicine", description="Specialization"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=10, description="Years of experience"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="description", type="string", example="Experienced physiotherapist...", description="Description/about (translatable)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Physiotherapist created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Physiotherapist created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/physiotherapists/...")
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
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'job_name' => 'nullable|string|max:255',
            'job_specification' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'years_of_experience' => 'nullable|integer|min:0',
            'locale' => 'nullable|string|in:en,ar',
            'description' => 'nullable|string',
        ]);

        $locale = $validatedData['locale'] ?? 'en';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'physiotherapists');
        }

        $physiotherapist = Physiotherapist::create([
            'name' => $validatedData['name'],
            'price' => $validatedData['price'],
            'image' => $imagePath,
            'job_name' => $request->job_name ?? null,
            'job_specification' => $request->job_specification ?? null,
            'specialization' => $request->specialization ?? null,
            'years_of_experience' => $request->years_of_experience ?? null,
        ]);

        $physiotherapist->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'description' => $request->description ?? null,
        ]);

        // Automatically create area prices for all areas using the base price
        $areas = \App\Models\Area::all();
        foreach ($areas as $area) {
            \App\Models\PhysiotherapistAreaPrice::create([
                'physiotherapist_id' => $physiotherapist->id,
                'area_id' => $area->id,
                'price' => $validatedData['price'],
            ]);
        }

        $translation = $physiotherapist->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Physiotherapist created successfully.',
            'data' => [
                'id' => $physiotherapist->id,
                'name' => $translation ? $translation->name : $physiotherapist->name,
                'price' => $physiotherapist->price,
                'image' => $physiotherapist->image_url,
                'job_name' => $physiotherapist->job_name,
                'job_specification' => $physiotherapist->job_specification,
                'specialization' => $physiotherapist->specialization,
                'years_of_experience' => $physiotherapist->years_of_experience,
                'description' => $translation?->description,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/physiotherapists/{id}",
     *     summary="Get a specific physiotherapist",
     *     description="Retrieve a specific physiotherapist with translations based on Accept-Language header",
     *     tags={"Admin - Physiotherapists"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist ID",
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
     *         description="Physiotherapist retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith", description="Physiotherapist name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/physiotherapists/..."),
     *                 @OA\Property(property="job_name", type="string", example="Senior Physiotherapist"),
     *                 @OA\Property(property="job_specification", type="string", example="Musculoskeletal Specialist"),
     *                 @OA\Property(property="specialization", type="string", example="Sports Medicine"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=10),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Physiotherapist not found")
     * )
     */
    public function show(Physiotherapist $physiotherapist): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $physiotherapist->translate($locale);
        
        $data = [
            'id' => $physiotherapist->id,
            'name' => $translation ? $translation->name : $physiotherapist->name,
            'price' => $physiotherapist->price,
            'image' => $physiotherapist->image_url,
            'job_name' => $physiotherapist->job_name,
            'job_specification' => $physiotherapist->job_specification,
            'specialization' => $physiotherapist->specialization,
            'years_of_experience' => $physiotherapist->years_of_experience,
        ];
        
        if ($translation) {
            $data['description'] = $translation->description;
        } else {
            $data['description'] = null;
        }
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/physiotherapists/{id}",
     *     summary="Update a physiotherapist",
     *     description="Update an existing physiotherapist. Use POST with _method=PUT for file uploads. Supports multipart/form-data.",
     *     tags={"Admin - Physiotherapists"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method."),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith - Updated"),
     *                 @OA\Property(property="price", type="number", format="float", example=220.00),
     *                 @OA\Property(property="image", type="string", format="binary", description="New image (optional)"),
     *                 @OA\Property(property="job_name", type="string", example="Senior Physiotherapist"),
     *                 @OA\Property(property="job_specification", type="string", example="Musculoskeletal Specialist"),
     *                 @OA\Property(property="specialization", type="string", example="Sports Medicine"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=12),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en"),
     *                 @OA\Property(property="description", type="string", example="Updated description")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physiotherapist updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Physiotherapist updated successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Physiotherapist $physiotherapist): JsonResponse
    {
        $locale = $request->input('locale');
        if (is_string($locale)) {
            $locale = trim($locale);
        }
        if (!$locale || !in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'job_name' => 'nullable|string|max:255',
            'job_specification' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'years_of_experience' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);
        
        $validatedData['locale'] = $locale;

        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $physiotherapist->image,
                'physiotherapists'
            );
            $physiotherapist->image = $imagePath;
        }

        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'price' => $validatedData['price'] ?? null,
            'job_name' => $request->job_name ?? null,
            'job_specification' => $request->job_specification ?? null,
            'specialization' => $request->specialization ?? null,
            'years_of_experience' => $request->years_of_experience ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $physiotherapist->update($updateData);
        }

        $physiotherapist->translations()->updateOrCreate(
            ['locale' => $validatedData['locale']],
            [
                'name' => $validatedData['name'] ?? $physiotherapist->name,
                'description' => $request->description ?? null,
            ]
        );

        $locale = app()->getLocale() ?: 'en';
        $translation = $physiotherapist->translate($locale);
        
        $data = [
            'id' => $physiotherapist->id,
            'name' => $translation ? $translation->name : $physiotherapist->name,
            'price' => $physiotherapist->price,
            'image' => $physiotherapist->image_url,
            'job_name' => $physiotherapist->job_name,
            'job_specification' => $physiotherapist->job_specification,
            'specialization' => $physiotherapist->specialization,
            'years_of_experience' => $physiotherapist->years_of_experience,
        ];
        
        if ($translation) {
            $data['description'] = $translation->description;
        } else {
            $data['description'] = null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Physiotherapist updated successfully.',
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/physiotherapists/{id}",
     *     summary="Delete a physiotherapist",
     *     description="Delete a physiotherapist",
     *     tags={"Admin - Physiotherapists"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physiotherapist deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Physiotherapist deleted successfully.")
     *         )
     *     )
     * )
     */
    public function destroy(Physiotherapist $physiotherapist): JsonResponse
    {
        if ($physiotherapist->image) {
            $this->imageStorageService->deleteImage($physiotherapist->image);
        }

        $physiotherapist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Physiotherapist deleted successfully.',
        ], 200);
    }
}

