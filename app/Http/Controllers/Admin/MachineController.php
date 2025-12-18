<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Machines",
 *     description="API Endpoints for managing Machines (Admin only)"
 * )
 */
class MachineController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/machines",
     *     summary="List all machines",
     *     description="Retrieve all machines with translations based on Accept-Language header",
     *     tags={"Admin - Machines"},
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
     *         description="Machines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/..."),
     *                 @OA\Property(property="description", type="string", example="Machine description...", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
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
        
        $machines = Machine::all();
        
        return response()->json([
            'success' => true,
            'data' => $machines->map(function ($machine) use ($locale) {
                $translation = $machine->translate($locale);
                return [
                    'id' => $machine->id,
                    'name' => $translation ? $translation->name : $machine->name,
                    'price' => $machine->price,
                    'image' => $machine->image_url,
                    'description' => $translation ? $translation->description : null,
                    'additional_information' => $translation ? $translation->additional_information : null,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/machines",
     *     summary="Create a new machine",
     *     description="Create a new machine with translations. Supports multipart/form-data for image upload.",
     *     tags={"Admin - Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","price"},
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00, description="Machine price (same for all areas initially)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Machine image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="description", type="string", example="Machine description", description="Machine description (translatable)"),
     *                 @OA\Property(property="additional_information", type="string", example="Additional information", description="Additional information (translatable)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Machine created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Machine created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine"),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/...")
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
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);

        // Default locale to 'en' if not provided
        $locale = $validatedData['locale'] ?? 'en';

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'machines');
        }

        // Create machine
        $machine = Machine::create([
            'name' => $validatedData['name'],
            'price' => $validatedData['price'],
            'image' => $imagePath,
        ]);

        // Create translation
        $machine->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'description' => $request->description ?? null,
            'additional_information' => $request->additional_information ?? null,
        ]);

        // Automatically create area prices for all areas using the base price
        $areas = \App\Models\Area::all();
        foreach ($areas as $area) {
            \App\Models\MachineAreaPrice::create([
                'machine_id' => $machine->id,
                'area_id' => $area->id,
                'price' => $validatedData['price'], // Use the same base price for all areas initially
            ]);
        }

        // Get translation for response
        $translation = $machine->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Machine created successfully.',
            'data' => [
                'id' => $machine->id,
                'name' => $translation ? $translation->name : $machine->name,
                'price' => $machine->price,
                'image' => $machine->image_url,
                'description' => $translation?->description,
                'additional_information' => $translation?->additional_information,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/machines/{id}",
     *     summary="Get a specific machine",
     *     description="Retrieve a specific machine with translations based on Accept-Language header",
     *     tags={"Admin - Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Machine ID",
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
     *         description="Machine retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/..."),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Machine not found")
     * )
     */
    public function show(Machine $machine): JsonResponse
    {
        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $machine->translate($locale);
        
        $data = [
            'id' => $machine->id,
            'name' => $translation ? $translation->name : $machine->name,
            'price' => $machine->price,
            'image' => $machine->image_url,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['description'] = $translation->description;
            $data['additional_information'] = $translation->additional_information;
        } else {
            $data['description'] = null;
            $data['additional_information'] = null;
        }
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/machines/{id}",
     *     summary="Update a machine",
     *     description="Update an existing machine. Use POST with _method=PUT for file uploads. Supports multipart/form-data.",
     *     tags={"Admin - Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Machine ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method. This enables Laravel method spoofing. Omit this field if using actual PUT request (without file uploads)."),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine - Updated", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=550.00),
     *                 @OA\Property(property="image", type="string", format="binary", description="New machine image (optional)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="additional_information", type="string", example="Updated additional information")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Machine updated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ventilator Machine - Updated", description="Machine name (translatable)"),
     *                 @OA\Property(property="price", type="number", format="float", example=550.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/machines/..."),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translation based on Accept-Language header (falls back to 'en')")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Machine not found")
     * )
     */
    public function update(Request $request, Machine $machine): JsonResponse
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
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
        ]);
        
        // Add locale to validated data
        $validatedData['locale'] = $locale;

        // Update image if provided
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $machine->image,
                'machines'
            );
            $machine->image = $imagePath;
        }

        // Update machine fields
        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'price' => $validatedData['price'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $machine->update($updateData);
        }

        // Update or create translation
        $machine->translations()->updateOrCreate(
            ['locale' => $validatedData['locale']],
            [
                'name' => $validatedData['name'] ?? $machine->name,
                'description' => $request->description ?? null,
                'additional_information' => $request->additional_information ?? null,
            ]
        );

        // Get current locale (from Accept-Language header or default to 'en')
        $locale = app()->getLocale() ?: 'en';
        
        // Get translation for current locale (with fallback to 'en')
        $translation = $machine->translate($locale);
        
        $data = [
            'id' => $machine->id,
            'name' => $translation ? $translation->name : $machine->name,
            'price' => $machine->price,
            'image' => $machine->image_url,
        ];
        
        // Add translation fields directly (not in a "translations" array)
        if ($translation) {
            $data['description'] = $translation->description;
            $data['additional_information'] = $translation->additional_information;
        } else {
            $data['description'] = null;
            $data['additional_information'] = null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Machine updated successfully.',
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/machines/{id}",
     *     summary="Delete a machine",
     *     description="Delete a machine (soft delete if supported, otherwise hard delete)",
     *     tags={"Admin - Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Machine ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Machine deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Machine not found")
     * )
     */
    public function destroy(Machine $machine): JsonResponse
    {
        // Delete image if exists
        if ($machine->image) {
            $this->imageStorageService->deleteImage($machine->image);
        }

        $machine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Machine deleted successfully.',
        ], 200);
    }
}

