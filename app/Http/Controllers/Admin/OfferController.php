<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Offers",
 *     description="API Endpoints for managing Offers (Admin only)"
 * )
 */
class OfferController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/offers",
     *     summary="List all offers",
     *     description="Retrieve all offers with translations based on Accept-Language header",
     *     tags={"Admin - Offers"},
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
     *         description="Offers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Special Service Offer", description="Offer name (translatable)"),
     *                 @OA\Property(property="offer_price", type="number", format="float", example=10.00),
     *                 @OA\Property(property="old_price", type="number", format="float", example=20.00),
     *                 @OA\Property(property="offer_available_until", type="string", example="3 Days"),
     *                 @OA\Property(property="category_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/offers/..."),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Translation based on Accept-Language header")
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
        
        $offers = Offer::with('category')->get();
        
        return response()->json([
            'success' => true,
            'data' => $offers->map(function ($offer) use ($locale) {
                $translation = $offer->translate($locale);
                return [
                    'id' => $offer->id,
                    'name' => $translation ? $translation->name : $offer->name,
                    'offer_price' => $offer->offer_price,
                    'old_price' => $offer->old_price,
                    'offer_available_until' => $offer->offer_available_until,
                    'category_id' => $offer->category_id,
                    'category' => $offer->category ? [
                        'id' => $offer->category->id,
                        'name' => $offer->category->name,
                    ] : null,
                    'image' => $offer->image_url,
                    'description' => $translation?->description,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/offers",
     *     summary="Create a new offer",
     *     description="Create a new offer with translations. Supports multipart/form-data for image upload.",
     *     tags={"Admin - Offers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","offer_price","old_price","offer_available_until"},
     *                 @OA\Property(property="name", type="string", example="Special Service Offer", description="Offer name (translatable)"),
     *                 @OA\Property(property="offer_price", type="number", format="float", example=10.00, description="Base offer price (same for all areas initially)"),
     *                 @OA\Property(property="old_price", type="number", format="float", example=20.00, description="Base old price (same for all areas initially)"),
     *                 @OA\Property(property="offer_available_until", type="string", example="3 Days", description="Offer availability (e.g., '3 Days', '2026-01-31')"),
     *                 @OA\Property(property="category_id", type="integer", example=1, nullable=true, description="Optional: Link to a category"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Offer image file (jpg, png, max 2MB)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="description", type="string", example="Special offer description...", description="Description (translatable)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Offer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Offer created successfully."),
     *             @OA\Property(property="data", type="object")
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
            'offer_price' => 'required|numeric|min:0',
            'old_price' => 'required|numeric|min:0',
            'offer_available_until' => 'required|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'locale' => 'nullable|string|in:en,ar',
            'description' => 'nullable|string',
        ]);

        $locale = $validatedData['locale'] ?? 'en';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'offers');
        }

        $offer = Offer::create([
            'name' => $validatedData['name'],
            'offer_price' => $validatedData['offer_price'],
            'old_price' => $validatedData['old_price'],
            'offer_available_until' => $validatedData['offer_available_until'],
            'category_id' => $request->category_id ?? null,
            'image' => $imagePath,
        ]);

        $offer->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'description' => $request->description ?? null,
        ]);

        // Automatically create area prices for all areas using the base prices
        $areas = \App\Models\Area::all();
        foreach ($areas as $area) {
            \App\Models\OfferAreaPrice::create([
                'offer_id' => $offer->id,
                'area_id' => $area->id,
                'offer_price' => $validatedData['offer_price'], // Use base offer price for all areas initially
                'old_price' => $validatedData['old_price'], // Use base old price for all areas initially
            ]);
        }

        $locale = app()->getLocale() ?: 'en';
        $translation = $offer->translate($locale);

        return response()->json([
            'success' => true,
            'message' => 'Offer created successfully.',
            'data' => [
                'id' => $offer->id,
                'name' => $translation ? $translation->name : $offer->name,
                'offer_price' => $offer->offer_price,
                'old_price' => $offer->old_price,
                'offer_available_until' => $offer->offer_available_until,
                'category_id' => $offer->category_id,
                'image' => $offer->image_url,
                'description' => $translation?->description,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/offers/{id}",
     *     summary="Get a specific offer",
     *     description="Retrieve a specific offer with translations based on Accept-Language header",
     *     tags={"Admin - Offers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Offer ID",
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
     *         description="Offer retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Offer not found")
     * )
     */
    public function show(Offer $offer): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $translation = $offer->translate($locale);
        
        $data = [
            'id' => $offer->id,
            'name' => $translation ? $translation->name : $offer->name,
            'offer_price' => $offer->offer_price,
            'old_price' => $offer->old_price,
            'offer_available_until' => $offer->offer_available_until,
            'category_id' => $offer->category_id,
            'category' => $offer->category ? [
                'id' => $offer->category->id,
                'name' => $offer->category->name,
            ] : null,
            'image' => $offer->image_url,
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
     *     path="/api/admin/offers/{id}",
     *     summary="Update an offer",
     *     description="Update an existing offer. Use POST with _method=PUT for file uploads. Supports multipart/form-data.",
     *     tags={"Admin - Offers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Offer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method."),
     *                 @OA\Property(property="name", type="string", example="Special Service Offer - Updated"),
     *                 @OA\Property(property="offer_price", type="number", format="float", example=12.00),
     *                 @OA\Property(property="old_price", type="number", format="float", example=25.00),
     *                 @OA\Property(property="offer_available_until", type="string", example="5 Days"),
     *                 @OA\Property(property="category_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="image", type="string", format="binary", description="New image (optional)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en"),
     *                 @OA\Property(property="description", type="string", example="Updated description")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Offer updated successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Offer $offer): JsonResponse
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
            'offer_price' => 'sometimes|numeric|min:0',
            'old_price' => 'sometimes|numeric|min:0',
            'offer_available_until' => 'sometimes|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
        ]);
        
        $validatedData['locale'] = $locale;

        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $offer->image,
                'offers'
            );
            $offer->image = $imagePath;
        }

        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'offer_price' => $validatedData['offer_price'] ?? null,
            'old_price' => $validatedData['old_price'] ?? null,
            'offer_available_until' => $request->offer_available_until ?? null,
            'category_id' => $request->category_id ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $offer->update($updateData);
        }

        $offer->translations()->updateOrCreate(
            ['locale' => $validatedData['locale']],
            [
                'name' => $validatedData['name'] ?? $offer->name,
                'description' => $request->description ?? null,
            ]
        );

        $locale = app()->getLocale() ?: 'en';
        $translation = $offer->translate($locale);
        
        $data = [
            'id' => $offer->id,
            'name' => $translation ? $translation->name : $offer->name,
            'offer_price' => $offer->offer_price,
            'old_price' => $offer->old_price,
            'offer_available_until' => $offer->offer_available_until,
            'category_id' => $offer->category_id,
            'category' => $offer->category ? [
                'id' => $offer->category->id,
                'name' => $offer->category->name,
            ] : null,
            'image' => $offer->image_url,
        ];
        
        if ($translation) {
            $data['description'] = $translation->description;
        } else {
            $data['description'] = null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully.',
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/offers/{id}",
     *     summary="Delete an offer",
     *     description="Delete an offer",
     *     tags={"Admin - Offers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Offer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Offer deleted successfully.")
     *         )
     *     )
     * )
     */
    public function destroy(Offer $offer): JsonResponse
    {
        if ($offer->image) {
            $this->imageStorageService->deleteImage($offer->image);
        }

        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offer deleted successfully.',
        ], 200);
    }
}

