<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorOperationAreaPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Doctor Operation Area Prices",
 *     description="API Endpoints for managing Doctor Operation Area-Based Pricing (Admin only)"
 * )
 */
class DoctorOperationAreaPriceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/doctor-operation-area-prices",
     *     summary="List all doctor operation area prices",
     *     description="Retrieve all doctor operation area prices, optionally filtered by operation_id.",
     *     tags={"Admin - Doctor Operation Area Prices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="operation_id",
     *         in="query",
     *         required=false,
     *         description="Filter by doctor operation ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $operationId = $request->query('operation_id');
        $query = DoctorOperationAreaPrice::with('area');
        if ($operationId) {
            $query->where('doctor_operation_id', $operationId);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/doctor-operation-area-prices",
     *     summary="Create a new doctor operation area price",
     *     tags={"Admin - Doctor Operation Area Prices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"doctor_operation_id", "area_id", "price"},
     *             @OA\Property(property="doctor_operation_id", type="integer", example=1),
     *             @OA\Property(property="area_id", type="integer", example=1),
     *             @OA\Property(property="price", type="number", format="float", example=5000.00)
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
            'doctor_operation_id' => 'required|exists:doctor_operations,id',
            'area_id' => 'required|exists:areas,id',
            'price' => 'required|numeric|min:0',
        ]);
        $record = DoctorOperationAreaPrice::create($validated);
        return response()->json(['success' => true, 'data' => $record], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/doctor-operation-area-prices/{id}",
     *     summary="Update doctor operation area price",
     *     tags={"Admin - Doctor Operation Area Prices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price"},
     *             @OA\Property(property="price", type="number", format="float", example=5500.00)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $record = DoctorOperationAreaPrice::findOrFail($id);
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);
        $record->update($validated);
        return response()->json(['success' => true, 'data' => $record]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/doctor-operation-area-prices/{id}",
     *     summary="Delete doctor operation area price",
     *     tags={"Admin - Doctor Operation Area Prices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $record = DoctorOperationAreaPrice::findOrFail($id);
        $record->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}

