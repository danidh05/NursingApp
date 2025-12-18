<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Area;
use App\Models\MachineAreaPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MachineAreaPriceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/machine-area-prices",
     *     summary="List all machine area prices (Admin only)",
     *     description="Retrieve a list of all machine area prices for admin management.",
     *     tags={"Admin - Machine Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Machine area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="machine_area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="machine_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="machine", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Ventilator Machine")
     *                 ),
     *                 @OA\Property(property="area", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Beirut")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function index()
    {
        $machineAreaPrices = MachineAreaPrice::with(['machine:id,name', 'area:id,name'])
            ->orderBy('machine_id')
            ->orderBy('area_id')
            ->get();

        return response()->json(['machine_area_prices' => $machineAreaPrices], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/machine-area-prices",
     *     summary="Create machine area price (Admin only)",
     *     description="Create a new machine area price for region-based pricing.",
     *     tags={"Admin - Machine Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"machine_id","area_id","price"},
     *             @OA\Property(property="machine_id", type="integer", example=1, description="Machine ID"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="Area ID"),
     *             @OA\Property(property="price", type="number", format="float", example=500.00, description="Price for this machine in this area")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Machine area price created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Machine area price created successfully."),
     *             @OA\Property(property="machine_area_price", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="machine_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machine_id' => 'required|exists:machines,id',
            'area_id' => 'required|exists:areas,id',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if this machine-area combination already exists
        $existingPrice = MachineAreaPrice::where('machine_id', $request->machine_id)
            ->where('area_id', $request->area_id)
            ->first();

        if ($existingPrice) {
            return response()->json([
                'message' => 'A price for this machine and area combination already exists. Use update instead.'
            ], 422);
        }

        $machineAreaPrice = MachineAreaPrice::create([
            'machine_id' => $request->machine_id,
            'area_id' => $request->area_id,
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Machine area price created successfully.',
            'machine_area_price' => $machineAreaPrice
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/machine-area-prices/{id}",
     *     summary="Update machine area price (Admin only)",
     *     description="Update an existing machine area price.",
     *     tags={"Admin - Machine Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Machine area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price"},
     *             @OA\Property(property="price", type="number", format="float", example=550.00, description="Updated price for this machine in this area")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine area price updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Machine area price updated successfully."),
     *             @OA\Property(property="machine_area_price", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="machine_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=550.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Machine area price not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $machineAreaPrice = MachineAreaPrice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $machineAreaPrice->update([
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Machine area price updated successfully.',
            'machine_area_price' => $machineAreaPrice
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/machine-area-prices/{id}",
     *     summary="Delete machine area price (Admin only)",
     *     description="Delete a machine area price.",
     *     tags={"Admin - Machine Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Machine area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine area price deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Machine area price deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Machine area price not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $machineAreaPrice = MachineAreaPrice::findOrFail($id);
        $machineAreaPrice->delete();

        return response()->json([
            'message' => 'Machine area price deleted successfully.'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/machine-area-prices/machine/{machineId}",
     *     summary="Get prices for a specific machine (Admin only)",
     *     description="Retrieve all area prices for a specific machine.",
     *     tags={"Admin - Machine Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="machineId",
     *         in="path",
     *         required=true,
     *         description="Machine ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="machine", type="object"),
     *             @OA\Property(property="area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="area", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Beirut")
     *                 )
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Machine not found"
     *     )
     * )
     */
    public function getMachinePrices($machineId)
    {
        $machine = Machine::findOrFail($machineId);
        $areaPrices = MachineAreaPrice::where('machine_id', $machineId)
            ->with('area:id,name')
            ->get();

        return response()->json([
            'machine' => $machine,
            'area_prices' => $areaPrices
        ], 200);
    }
}

