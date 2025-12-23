<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Doctor;
use App\Models\DoctorOperation;
use App\Models\DoctorOperationAreaPrice;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorOperationController extends Controller
{
    protected ImageStorageService $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    public function index(Request $request): JsonResponse
    {
        $doctorId = $request->query('doctor_id');
        $query = DoctorOperation::query()->with(['areaPrices.area', 'doctor']);
        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }
        $ops = $query->get();
        $locale = app()->getLocale() ?: 'en';
        return response()->json([
            'success' => true,
            'data' => $ops->map(function ($op) use ($locale) {
                $t = $op->translate($locale);
                return [
                    'id' => $op->id,
                    'doctor_id' => $op->doctor_id,
                    'name' => $t?->name ?? $op->name,
                    'price' => $op->price,
                    'image' => $op->image_url,
                    'description' => $t?->description ?? $op->description,
                    'additional_information' => $t?->additional_information ?? $op->additional_information,
                    'building_name' => $op->building_name,
                    'location_description' => $op->location_description,
                    'area_prices' => $op->areaPrices->map(function ($ap) {
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
            'doctor_id' => 'required|exists:doctors,id',
            'name' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
            'building_name' => 'nullable|string',
            'location_description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'locale' => 'nullable|string|in:en,ar',
            'area_prices' => 'nullable|array',
            'area_prices.*.area_id' => 'required_with:area_prices|exists:areas,id',
            'area_prices.*.price' => 'required_with:area_prices|numeric|min:0',
        ]);
        $locale = $validated['locale'] ?? 'en';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'doctor-operations');
        }

        $op = DoctorOperation::create([
            'doctor_id' => $validated['doctor_id'],
            'name' => $validated['name'],
            'price' => $validated['price'] ?? null,
            'description' => $validated['description'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
            'building_name' => $validated['building_name'] ?? null,
            'location_description' => $validated['location_description'] ?? null,
            'image' => $imagePath,
        ]);
        $op->translations()->create([
            'locale' => $locale,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        if (!empty($validated['area_prices']) && is_array($validated['area_prices'])) {
            foreach ($validated['area_prices'] as $ap) {
                DoctorOperationAreaPrice::create([
                    'doctor_operation_id' => $op->id,
                    'area_id' => $ap['area_id'],
                    'price' => $ap['price'],
                ]);
            }
        } else {
            $areas = Area::all();
            foreach ($areas as $area) {
                DoctorOperationAreaPrice::create([
                    'doctor_operation_id' => $op->id,
                    'area_id' => $area->id,
                    'price' => $validated['price'] ?? 0,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Operation created', 'data' => ['id' => $op->id]], 201);
    }

    public function show(DoctorOperation $doctorOperation): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $doctorOperation->load('areaPrices.area');
        $t = $doctorOperation->translate($locale);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctorOperation->id,
                'doctor_id' => $doctorOperation->doctor_id,
                'name' => $t?->name ?? $doctorOperation->name,
                'price' => $doctorOperation->price,
                'image' => $doctorOperation->image_url,
                'description' => $t?->description ?? $doctorOperation->description,
                'additional_information' => $t?->additional_information ?? $doctorOperation->additional_information,
                'building_name' => $doctorOperation->building_name,
                'location_description' => $doctorOperation->location_description,
                'area_prices' => $doctorOperation->areaPrices->map(function ($ap) {
                    return [
                        'area_id' => $ap->area_id,
                        'area_name' => $ap->area->name ?? null,
                        'price' => $ap->price,
                    ];
                }),
            ],
        ]);
    }

    public function update(Request $request, DoctorOperation $doctorOperation): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'additional_information' => 'nullable|string',
            'building_name' => 'nullable|string',
            'location_description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'locale' => 'nullable|string|in:en,ar',
            'area_prices' => 'nullable|array',
            'area_prices.*.area_id' => 'required_with:area_prices|exists:areas,id',
            'area_prices.*.price' => 'required_with:area_prices|numeric|min:0',
        ]);
        $locale = $validated['locale'] ?? 'en';

        if ($request->hasFile('image')) {
            $doctorOperation->image = $this->imageStorageService->updateImage($request->file('image'), $doctorOperation->image, 'doctor-operations');
        }
        foreach (['name','price','description','additional_information','building_name','location_description'] as $field) {
            if (array_key_exists($field, $validated)) {
                $doctorOperation->$field = $validated[$field];
            }
        }
        $doctorOperation->save();

        $tr = $doctorOperation->translations()->where('locale', $locale)->first();
        if ($tr) {
            $tr->update([
                'name' => $validated['name'] ?? $tr->name,
                'description' => $validated['description'] ?? $tr->description,
                'additional_information' => $validated['additional_information'] ?? $tr->additional_information,
            ]);
        } else {
            $doctorOperation->translations()->create([
                'locale' => $locale,
                'name' => $validated['name'] ?? null,
                'description' => $validated['description'] ?? null,
                'additional_information' => $validated['additional_information'] ?? null,
            ]);
        }

        if (isset($validated['area_prices'])) {
            DoctorOperationAreaPrice::where('doctor_operation_id', $doctorOperation->id)->delete();
            foreach ($validated['area_prices'] as $ap) {
                DoctorOperationAreaPrice::create([
                    'doctor_operation_id' => $doctorOperation->id,
                    'area_id' => $ap['area_id'],
                    'price' => $ap['price'],
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Operation updated']);
    }

    public function destroy(DoctorOperation $doctorOperation): JsonResponse
    {
        if ($doctorOperation->image) {
            $this->imageStorageService->deleteImage($doctorOperation->image);
        }
        $doctorOperation->delete();
        return response()->json(['success' => true, 'message' => 'Operation deleted']);
    }
}

