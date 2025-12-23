<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function getDoctorsByCategory(Request $request, int $id): JsonResponse
    {
        $areaId = $request->query('area_id');
        $locale = app()->getLocale() ?: 'en';

        $category = DoctorCategory::findOrFail($id);
        $doctors = Doctor::with(['areaPrices.area', 'doctorCategory'])
            ->where('doctor_category_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $doctors->map(function ($doc) use ($locale, $areaId) {
                $t = $doc->translate($locale);
                $areaPrice = $areaId ? $doc->areaPrices->firstWhere('area_id', $areaId) : null;
                $price = $areaPrice ? $areaPrice->price : $doc->price;
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'image' => $doc->image_url,
                    'price' => $price,
                    'specification' => $t?->specification ?? $doc->specification,
                    'job_name' => $t?->job_name ?? $doc->job_name,
                    'description' => $t?->description ?? $doc->description,
                    'additional_information' => $t?->additional_information ?? $doc->additional_information,
                    'years_of_experience' => $doc->years_of_experience,
                ];
            }),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $areaId = $request->query('area_id');
        $weekStart = $request->query('week_start'); // YYYY-MM-DD optional
        $weekEnd = $request->query('week_end');     // YYYY-MM-DD optional

        $doctor = Doctor::with([
            'areaPrices.area',
            'operations.areaPrices.area',
            'operations.translations',
            'doctorCategory',
            'availabilities' => function ($q) use ($weekStart, $weekEnd) {
                if ($weekStart) {
                    $q->where('date', '>=', $weekStart);
                }
                if ($weekEnd) {
                    $q->where('date', '<=', $weekEnd);
                }
                $q->orderBy('date')->orderBy('start_time');
            },
        ])->findOrFail($id);

        $t = $doctor->translate($locale);
        $areaPrice = $areaId ? $doctor->areaPrices->firstWhere('area_id', $areaId) : null;
        $price = $areaPrice ? $areaPrice->price : $doctor->price;

        $operations = $doctor->operations->map(function ($op) use ($locale, $areaId) {
            $t = $op->translate($locale);
            $ap = $areaId ? $op->areaPrices->firstWhere('area_id', $areaId) : null;
            $price = $ap ? $ap->price : $op->price;
            return [
                'id' => $op->id,
                'name' => $t?->name ?? $op->name,
                'price' => $price,
                'image' => $op->image_url,
                'description' => $t?->description ?? $op->description,
                'additional_information' => $t?->additional_information ?? $op->additional_information,
                'building_name' => $op->building_name,
                'location_description' => $op->location_description,
            ];
        });

        $availabilities = $doctor->availabilities->filter(function ($slot) {
            return !$slot->is_booked;
        })->values()->map(function ($slot) {
            return [
                'id' => $slot->id,
                'date' => $slot->date?->format('Y-m-d'),
                'start_time' => $slot->start_time ? Carbon::parse($slot->start_time)->format('H:i:s') : null,
                'end_time' => $slot->end_time ? Carbon::parse($slot->end_time)->format('H:i:s') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctor->id,
                'category_id' => $doctor->doctor_category_id,
                'name' => $doctor->name,
                'price' => $price,
                'image' => $doctor->image_url,
                'specification' => $t?->specification ?? $doctor->specification,
                'job_name' => $t?->job_name ?? $doctor->job_name,
                'description' => $t?->description ?? $doctor->description,
                'additional_information' => $t?->additional_information ?? $doctor->additional_information,
                'years_of_experience' => $doctor->years_of_experience,
                'operations' => $operations,
                'availabilities' => $availabilities,
            ],
        ]);
    }
}

