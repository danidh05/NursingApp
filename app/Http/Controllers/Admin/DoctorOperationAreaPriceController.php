<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorOperationAreaPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorOperationAreaPriceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $operationId = $request->query('operation_id');
        $query = DoctorOperationAreaPrice::with('area');
        if ($operationId) {
            $query->where('doctor_operation_id', $operationId);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

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

    public function update(Request $request, $id): JsonResponse
    {
        $record = DoctorOperationAreaPrice::findOrFail($id);
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);
        $record->update($validated);
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroy($id): JsonResponse
    {
        $record = DoctorOperationAreaPrice::findOrFail($id);
        $record->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}

