<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Doctor;
use App\Models\DoctorAreaPrice;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

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

    public function destroy(Doctor $doctor): JsonResponse
    {
        if ($doctor->image) {
            $this->imageStorageService->deleteImage($doctor->image);
        }
        $doctor->delete();
        return response()->json(['success' => true, 'message' => 'Doctor deleted']);
    }
}

