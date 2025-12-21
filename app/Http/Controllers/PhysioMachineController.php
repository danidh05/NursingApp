<?php

namespace App\Http\Controllers;

use App\Models\PhysioMachine;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Physio Machines",
 *     description="API Endpoints for viewing Physio Machines (User accessible)"
 * )
 */
class PhysioMachineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/physio-machines",
     *     summary="List all physio machines",
     *     description="Retrieve all physio machines available for Category 5 requests",
     *     tags={"Physio Machines"},
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
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
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
     * @OA\Get(
     *     path="/api/physio-machines/{id}",
     *     summary="Get a specific physio machine",
     *     description="Retrieve a specific physio machine",
     *     tags={"Physio Machines"},
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
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Physio machine not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $machine = PhysioMachine::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $machine,
        ], 200);
    }
}

