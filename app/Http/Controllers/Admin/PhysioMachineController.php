<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhysioMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Physio Machines",
 *     description="API Endpoints for managing Physio Machines (Admin only)"
 * )
 */
class PhysioMachineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/physio-machines",
     *     summary="List all physio machines",
     *     description="Retrieve all physio machines",
     *     tags={"Admin - Physio Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Physio machines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="TENS Machine"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             ))
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $machines = PhysioMachine::all();
        
        return response()->json([
            'success' => true,
            'data' => $machines,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/physio-machines",
     *     summary="Create a new physio machine",
     *     description="Create a new physio machine",
     *     tags={"Admin - Physio Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price"},
     *             @OA\Property(property="name", type="string", example="TENS Machine"),
     *             @OA\Property(property="price", type="number", format="float", example=50.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Physio machine created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Physio machine created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="TENS Machine"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $machine = PhysioMachine::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Physio machine created successfully.',
            'data' => $machine,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/physio-machines/{id}",
     *     summary="Get a specific physio machine",
     *     description="Retrieve a specific physio machine",
     *     tags={"Admin - Physio Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physio Machine ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physio machine retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="TENS Machine"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             )
     *         )
     *     )
     * )
     */
    public function show(PhysioMachine $physioMachine): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $physioMachine,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/physio-machines/{id}",
     *     summary="Update a physio machine",
     *     description="Update an existing physio machine",
     *     tags={"Admin - Physio Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physio Machine ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="TENS Machine - Updated"),
     *             @OA\Property(property="price", type="number", format="float", example=55.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physio machine updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Physio machine updated successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, PhysioMachine $physioMachine): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
        ]);

        $physioMachine->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Physio machine updated successfully.',
            'data' => $physioMachine,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/physio-machines/{id}",
     *     summary="Delete a physio machine",
     *     description="Delete a physio machine",
     *     tags={"Admin - Physio Machines"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physio Machine ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physio machine deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Physio machine deleted successfully.")
     *         )
     *     )
     * )
     */
    public function destroy(PhysioMachine $physioMachine): JsonResponse
    {
        $physioMachine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Physio machine deleted successfully.',
        ], 200);
    }
}

