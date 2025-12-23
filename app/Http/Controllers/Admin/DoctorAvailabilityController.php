<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorAvailabilityController extends Controller
{
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

    public function destroy(DoctorAvailability $doctorAvailability): JsonResponse
    {
        if ($doctorAvailability->is_booked) {
            return response()->json(['success' => false, 'message' => 'Slot is booked, cannot delete'], 422);
        }
        $doctorAvailability->delete();
        return response()->json(['success' => true, 'message' => 'Slot deleted']);
    }
}

