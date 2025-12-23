<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Doctor;
use App\Models\DoctorAreaPrice;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Doctors",
 *     description="API Endpoints for managing Doctors (Admin only)"
 * )
 */
class DoctorController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/doctors",
     *     summary="List all doctors",
     *     tags={"Admin - Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $doctors = Doctor::with(['doctorCategory', 'areaPrices.area'])->get();
        return response()->json([
            'success' => true,
            'data' => $doctors->map(function ($doc) use ($locale) {
                $t = $doc->translate($locale);
                return [
                    'id' => $doc->id,
                    'category_id' => $doc->doctor_category_id,
                    'name' => $doc->name,
                    'price' => $doc->price,
                    'image' => $doc->image_url,
                    'specification' => $t?->specification ?? $doc->specification,
                    'job_name' => $t?->job_name ?? $doc->job_name,
                    'description' => $t?->description ?? $doc->description,
                    'additional_information' => $t?->additional_information ?? $doc->additional_information,
                    'years_of_experience' => $doc->years_of_experience,
                    'area_prices' => $doc->areaPrices->map(function ($ap) {
                        return [
                            'area_id' => $ap->area_id,
                            'area_name' => $ap->area->name ?? null,
                            'price' => $ap->price,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/doctors",
     *     summary="Create a new doctor",
     *     description="Create a new doctor with translations and area-based pricing. If area_prices is not provided, automatically creates pricing for all areas using the base price.",
     *     tags={"Admin - Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"doctor_category_id", "name"},
     *                 @OA\Property(property="doctor_category_id", type="integer", example=1, description="Doctor category ID"),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith", description="Doctor name"),
     *                 @OA\Property(property="specification", type="string", example="Cardiologist", description="Doctor specification (translatable)"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=15, description="Years of experience"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Doctor image (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00, description="Base price (used for all areas if area_prices not provided)"),
     *                 @OA\Property(property="job_name", type="string", example="Senior Cardiologist", description="Job name (translatable)"),
     *                 @OA\Property(property="description", type="string", example="Expert in heart diseases", description="Description (translatable)"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Additional information (translatable)"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00)
     *                 ), description="Optional: Array of area-specific prices. If not provided, creates prices for all areas using base price.")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_category_id' => 'required|exists:doctor_categories,id',
            'name' => 'required|string',
            'specification' => 'nullable|string',
            'years_of_experience' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'price' => 'nullable|numeric|min:0',
            'job_name' => 'nullable|string',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
            'locale' => 'nullable|string|in:en,ar',
            'area_prices' => 'nullable|array',
            'area_prices.*.area_id' => 'required_with:area_prices|exists:areas,id',
            'area_prices.*.price' => 'required_with:area_prices|numeric|min:0',
        ]);
        $locale = $validated['locale'] ?? 'en';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'doctors');
        }

        $doctor = Doctor::create([
            'doctor_category_id' => $validated['doctor_category_id'],
            'name' => $validated['name'],
            'specification' => $validated['specification'] ?? null,
            'years_of_experience' => $validated['years_of_experience'] ?? null,
            'image' => $imagePath,
            'price' => $validated['price'] ?? null,
            'job_name' => $validated['job_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        $doctor->translations()->create([
            'locale' => $locale,
            'specification' => $validated['specification'] ?? null,
            'job_name' => $validated['job_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        if (!empty($validated['area_prices']) && is_array($validated['area_prices'])) {
            foreach ($validated['area_prices'] as $ap) {
                DoctorAreaPrice::create([
                    'doctor_id' => $doctor->id,
                    'area_id' => $ap['area_id'],
                    'price' => $ap['price'],
                ]);
            }
        } else {
            $areas = Area::all();
            foreach ($areas as $area) {
                DoctorAreaPrice::create([
                    'doctor_id' => $doctor->id,
                    'area_id' => $area->id,
                    'price' => $validated['price'] ?? 0,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Doctor created', 'data' => ['id' => $doctor->id]], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/doctors/{id}",
     *     summary="Get doctor details",
     *     tags={"Admin - Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function show(Doctor $doctor): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $doctor->load(['areaPrices.area', 'doctorCategory']);
        $t = $doctor->translate($locale);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctor->id,
                'category_id' => $doctor->doctor_category_id,
                'name' => $doctor->name,
                'price' => $doctor->price,
                'image' => $doctor->image_url,
                'specification' => $t?->specification ?? $doctor->specification,
                'job_name' => $t?->job_name ?? $doctor->job_name,
                'description' => $t?->description ?? $doctor->description,
                'additional_information' => $t?->additional_information ?? $doctor->additional_information,
                'years_of_experience' => $doctor->years_of_experience,
                'area_prices' => $doctor->areaPrices->map(function ($ap) {
                    return [
                        'area_id' => $ap->area_id,
                        'area_name' => $ap->area->name ?? null,
                        'price' => $ap->price,
                    ];
                }),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/doctors/{id}",
     *     summary="Update doctor",
     *     description="Update a doctor. Use POST with _method=PUT for file uploads. All fields are optional. If area_prices is provided, replaces all existing area prices.",
     *     tags={"Admin - Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="doctor_category_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="specification", type="string", example="Cardiologist", description="Translatable"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=15),
     *                 @OA\Property(property="image", type="string", format="binary", description="Doctor image (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="job_name", type="string", example="Senior Cardiologist", description="Translatable"),
     *                 @OA\Property(property="description", type="string", example="Expert in heart diseases", description="Translatable"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translatable"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)"),
     *                 @OA\Property(property="area_prices", type="array", @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00)
     *                 ), description="Optional: Array of area-specific prices. Replaces all existing area prices.")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validate([
            'doctor_category_id' => 'nullable|exists:doctor_categories,id',
            'name' => 'nullable|string',
            'specification' => 'nullable|string',
            'years_of_experience' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'price' => 'nullable|numeric|min:0',
            'job_name' => 'nullable|string',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
            'locale' => 'nullable|string|in:en,ar',
            'area_prices' => 'nullable|array',
            'area_prices.*.area_id' => 'required_with:area_prices|exists:areas,id',
            'area_prices.*.price' => 'required_with:area_prices|numeric|min:0',
        ]);
        $locale = $validated['locale'] ?? 'en';

        if ($request->hasFile('image')) {
            $doctor->image = $this->imageStorageService->updateImage($request->file('image'), $doctor->image, 'doctors');
        }
        foreach (['doctor_category_id','name','specification','years_of_experience','price','job_name','description','additional_information'] as $field) {
            if (array_key_exists($field, $validated)) {
                $doctor->$field = $validated[$field];
            }
        }
        $doctor->save();

        $tr = $doctor->translations()->where('locale', $locale)->first();
        if ($tr) {
            $tr->update([
                'specification' => $validated['specification'] ?? $tr->specification,
                'job_name' => $validated['job_name'] ?? $tr->job_name,
                'description' => $validated['description'] ?? $tr->description,
                'additional_information' => $validated['additional_information'] ?? $tr->additional_information,
            ]);
        } else {
            $doctor->translations()->create([
                'locale' => $locale,
                'specification' => $validated['specification'] ?? null,
                'job_name' => $validated['job_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'additional_information' => $validated['additional_information'] ?? null,
            ]);
        }

        if (isset($validated['area_prices'])) {
            DoctorAreaPrice::where('doctor_id', $doctor->id)->delete();
            foreach ($validated['area_prices'] as $ap) {
                DoctorAreaPrice::create([
                    'doctor_id' => $doctor->id,
                    'area_id' => $ap['area_id'],
                    'price' => $ap['price'],
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Doctor updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/doctors/{id}",
     *     summary="Delete doctor",
     *     tags={"Admin - Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy(Doctor $doctor): JsonResponse
    {
        if ($doctor->image) {
            $this->imageStorageService->deleteImage($doctor->image);
        }
        $doctor->delete();
        return response()->json(['success' => true, 'message' => 'Doctor deleted']);
    }
}

