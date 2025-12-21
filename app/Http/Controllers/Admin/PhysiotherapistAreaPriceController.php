<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Physiotherapist;
use App\Models\Area;
use App\Models\PhysiotherapistAreaPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhysiotherapistAreaPriceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/physiotherapist-area-prices",
     *     summary="List all physiotherapist area prices (Admin only)",
     *     description="Retrieve a list of all physiotherapist area prices for admin management.",
     *     tags={"Admin - Physiotherapist Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Physiotherapist area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="physiotherapist_area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="physiotherapist_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=200.00),
     *                 @OA\Property(property="physiotherapist", type="object"),
     *                 @OA\Property(property="area", type="object")
     *             ))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $prices = PhysiotherapistAreaPrice::with(['physiotherapist:id,name', 'area:id,name'])
            ->orderBy('physiotherapist_id')
            ->orderBy('area_id')
            ->get();

        return response()->json(['physiotherapist_area_prices' => $prices], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/physiotherapist-area-prices",
     *     summary="Create physiotherapist area price (Admin only)",
     *     description="Create a new physiotherapist area price for region-based pricing.",
     *     tags={"Admin - Physiotherapist Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"physiotherapist_id","area_id","price"},
     *             @OA\Property(property="physiotherapist_id", type="integer", example=1),
     *             @OA\Property(property="area_id", type="integer", example=1),
     *             @OA\Property(property="price", type="number", format="float", example=200.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Physiotherapist area price created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'physiotherapist_id' => 'required|exists:physiotherapists,id',
            'area_id' => 'required|exists:areas,id',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existingPrice = PhysiotherapistAreaPrice::where('physiotherapist_id', $request->physiotherapist_id)
            ->where('area_id', $request->area_id)
            ->first();

        if ($existingPrice) {
            return response()->json([
                'message' => 'A price for this physiotherapist and area combination already exists. Use update instead.'
            ], 422);
        }

        $price = PhysiotherapistAreaPrice::create([
            'physiotherapist_id' => $request->physiotherapist_id,
            'area_id' => $request->area_id,
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Physiotherapist area price created successfully.',
            'physiotherapist_area_price' => $price
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/physiotherapist-area-prices/{id}",
     *     summary="Update physiotherapist area price (Admin only)",
     *     description="Update an existing physiotherapist area price.",
     *     tags={"Admin - Physiotherapist Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price"},
     *             @OA\Property(property="price", type="number", format="float", example=220.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physiotherapist area price updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $price = PhysiotherapistAreaPrice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $price->update([
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Physiotherapist area price updated successfully.',
            'physiotherapist_area_price' => $price
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/physiotherapist-area-prices/{id}",
     *     summary="Delete physiotherapist area price (Admin only)",
     *     description="Delete a physiotherapist area price.",
     *     tags={"Admin - Physiotherapist Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physiotherapist area price deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $price = PhysiotherapistAreaPrice::findOrFail($id);
        $price->delete();

        return response()->json([
            'message' => 'Physiotherapist area price deleted successfully.'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/physiotherapist-area-prices/physiotherapist/{physiotherapistId}",
     *     summary="Get prices for a specific physiotherapist (Admin only)",
     *     description="Retrieve all area prices for a specific physiotherapist.",
     *     tags={"Admin - Physiotherapist Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="physiotherapistId",
     *         in="path",
     *         required=true,
     *         description="Physiotherapist ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Physiotherapist area prices retrieved successfully"
     *     )
     * )
     */
    public function getPhysiotherapistPrices($physiotherapistId)
    {
        $physiotherapist = Physiotherapist::findOrFail($physiotherapistId);
        $areaPrices = PhysiotherapistAreaPrice::where('physiotherapist_id', $physiotherapistId)
            ->with('area:id,name')
            ->get();

        return response()->json([
            'physiotherapist' => $physiotherapist,
            'area_prices' => $areaPrices
        ], 200);
    }
}

