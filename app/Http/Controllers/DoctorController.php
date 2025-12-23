<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Doctors",
 *     description="API Endpoints for viewing Doctors (Category 8)"
 * )
 */
class DoctorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor-categories/{id}/doctors",
     *     summary="Get doctors by category and area",
     *     description="Retrieve all doctors in a specific category, optionally filtered by area. Returns area-specific pricing if area_id is provided, otherwise returns base price. All fields are translated based on Accept-Language header.",
     *     tags={"Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Doctor category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="area_id",
     *         in="query",
     *         required=false,
     *         description="Area ID for area-specific pricing",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctors retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/doctors/..."),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00, description="Area-specific price if area_id provided, otherwise base price"),
     *                 @OA\Property(property="specification", type="string", example="Cardiologist", description="Translated"),
     *                 @OA\Property(property="job_name", type="string", example="Senior Cardiologist", description="Translated"),
     *                 @OA\Property(property="description", type="string", example="Expert in heart diseases", description="Translated"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translated"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=15)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Doctor category not found")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/doctors/{id}",
     *     summary="Get doctor details with operations and availabilities",
     *     description="Retrieve complete doctor details including operations, availability slots (filtered by week if provided), and area-specific pricing. Availability slots are filtered to show only unbooked slots. All fields are translated based on Accept-Language header.",
     *     tags={"Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Doctor ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="area_id",
     *         in="query",
     *         required=false,
     *         description="Area ID for area-specific pricing",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="week_start",
     *         in="query",
     *         required=false,
     *         description="Start date for availability filter (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2026-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="week_end",
     *         in="query",
     *         required=false,
     *         description="End date for availability filter (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2026-01-07")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/doctors/..."),
     *                 @OA\Property(property="specification", type="string", example="Cardiologist", description="Translated"),
     *                 @OA\Property(property="job_name", type="string", example="Senior Cardiologist", description="Translated"),
     *                 @OA\Property(property="description", type="string", example="Expert in heart diseases", description="Translated"),
     *                 @OA\Property(property="additional_information", type="string", nullable=true, description="Translated"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=15),
     *                 @OA\Property(property="operations", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Heart Surgery", description="Translated"),
     *                     @OA\Property(property="price", type="number", format="float", example=5000.00, description="Area-specific price if area_id provided"),
     *                     @OA\Property(property="image", type="string", example="http://localhost:8000/storage/doctor-operations/..."),
     *                     @OA\Property(property="description", type="string", description="Translated"),
     *                     @OA\Property(property="additional_information", type="string", nullable=true, description="Translated"),
     *                     @OA\Property(property="building_name", type="string", example="Medical Center Building A"),
     *                     @OA\Property(property="location_description", type="string", example="3rd floor, Room 301")
     *                 )),
     *                 @OA\Property(property="availabilities", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="date", type="string", format="date", example="2026-01-15"),
     *                     @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", example="10:00:00"),
     *                     description="Only unbooked slots are returned, filtered by week_start/week_end if provided"
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Doctor not found")
     * )
     */
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

