<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Doctor Availabilities",
 *     description="API Endpoints for managing Doctor Availability Slots (Admin only)"
 * )
 */
class DoctorAvailabilityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/doctor-availabilities",
     *     summary="List all doctor availability slots",
     *     description="Retrieve all doctor availability slots, optionally filtered by doctor_id.",
     *     tags={"Admin - Doctor Availabilities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="doctor_id",
     *         in="query",
     *         required=false,
     *         description="Filter by doctor ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $doctorId = $request->query('doctor_id');
        $query = DoctorAvailability::query()->with('doctor');
        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }
        $slots = $query->orderBy('date')->orderBy('start_time')->get();
        return response()->json(['success' => true, 'data' => $slots]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/doctor-availabilities",
     *     summary="Create a new availability slot",
     *     tags={"Admin - Doctor Availabilities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"doctor_id", "date", "start_time", "end_time"},
     *             @OA\Property(property="doctor_id", type="integer", example=1),
     *             @OA\Property(property="date", type="string", format="date", example="2026-01-15"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00", description="Time in HH:mm format"),
     *             @OA\Property(property="end_time", type="string", format="time", example="10:00", description="Time in HH:mm format, must be after start_time")
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
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        $slot = DoctorAvailability::create($validated);
        return response()->json(['success' => true, 'message' => 'Slot created', 'data' => $slot], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/doctor-availabilities/{id}",
     *     summary="Update availability slot",
     *     description="Update an availability slot. Use POST with _method=PUT. All fields are optional. If is_booked is set to false, clears booked_request_id.",
     *     tags={"Admin - Doctor Availabilities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_method", type="string", example="PUT"),
     *             @OA\Property(property="date", type="string", format="date", example="2026-01-15"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *             @OA\Property(property="end_time", type="string", format="time", example="10:00", description="Must be after start_time"),
     *             @OA\Property(property="is_booked", type="boolean", example=false, description="Booking status")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function update(Request $request, DoctorAvailability $doctorAvailability): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_booked' => 'nullable|boolean',
        ]);
        if ($doctorAvailability->is_booked && isset($validated['is_booked']) && $validated['is_booked'] === false) {
            // allow unbooking if admin wants to clear
            $doctorAvailability->booked_request_id = null;
        }
        $doctorAvailability->update($validated);
        return response()->json(['success' => true, 'message' => 'Slot updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/doctor-availabilities/{id}",
     *     summary="Delete availability slot",
     *     description="Delete an availability slot. Cannot delete if slot is booked.",
     *     tags={"Admin - Doctor Availabilities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Cannot delete booked slot"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy(DoctorAvailability $doctorAvailability): JsonResponse
    {
        if ($doctorAvailability->is_booked) {
            return response()->json(['success' => false, 'message' => 'Slot is booked, cannot delete'], 422);
        }
        $doctorAvailability->delete();
        return response()->json(['success' => true, 'message' => 'Slot deleted']);
    }
}

