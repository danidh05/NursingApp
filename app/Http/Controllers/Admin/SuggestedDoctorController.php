<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\SuggestedDoctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Suggested Doctors",
 *     description="API Endpoints for managing Suggested Doctors (Admin only)"
 * )
 */
class SuggestedDoctorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/suggested-doctors",
     *     summary="List all suggested doctors",
     *     tags={"Admin - Suggested Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $suggested = SuggestedDoctor::with(['doctor.doctorCategory', 'doctor.areaPrices.area'])
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $suggested->map(function ($item) use ($locale) {
                $doctor = $item->doctor;
                $translation = $doctor ? $doctor->translate($locale) : null;
                return [
                    'id' => $item->id,
                    'doctor_id' => $item->doctor_id,
                    'order' => $item->order,
                    'doctor' => $doctor ? [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'image' => $doctor->image_url,
                        'price' => $doctor->price,
                        'specification' => $translation?->specification ?? $doctor->specification,
                        'job_name' => $translation?->job_name ?? $doctor->job_name,
                        'description' => $translation?->description ?? $doctor->description,
                        'years_of_experience' => $doctor->years_of_experience,
                        'category_id' => $doctor->doctor_category_id,
                    ] : null,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/suggested-doctors",
     *     summary="Add a doctor to suggested",
     *     tags={"Admin - Suggested Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"doctor_id"},
     *             @OA\Property(property="doctor_id", type="integer", example=1),
     *             @OA\Property(property="order", type="integer", example=0, description="Display order (optional)")
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
            'doctor_id' => 'required|exists:doctors,id|unique:suggested_doctors,doctor_id',
            'order' => 'nullable|integer|min:0',
        ]);

        $suggested = SuggestedDoctor::create([
            'doctor_id' => $validated['doctor_id'],
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Doctor added to suggested',
            'data' => $suggested,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/suggested-doctors/{id}",
     *     summary="Update suggested doctor order",
     *     tags={"Admin - Suggested Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="integer", example=1, description="Display order"),
     *             @OA\Property(property="doctor_id", type="integer", example=2, description="Doctor ID (optional, to change doctor)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function update(Request $request, SuggestedDoctor $suggestedDoctor): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id|unique:suggested_doctors,doctor_id,' . $suggestedDoctor->id,
            'order' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['doctor_id'])) {
            $suggestedDoctor->doctor_id = $validated['doctor_id'];
        }
        if (isset($validated['order'])) {
            $suggestedDoctor->order = $validated['order'];
        }
        $suggestedDoctor->save();

        return response()->json([
            'success' => true,
            'message' => 'Suggested doctor updated',
            'data' => $suggestedDoctor,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/suggested-doctors/{id}",
     *     summary="Remove doctor from suggested",
     *     tags={"Admin - Suggested Doctors"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy(SuggestedDoctor $suggestedDoctor): JsonResponse
    {
        $suggestedDoctor->delete();
        return response()->json([
            'success' => true,
            'message' => 'Doctor removed from suggested',
        ]);
    }
}
