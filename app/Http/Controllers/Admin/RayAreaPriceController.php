<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ray;
use App\Models\Area;
use App\Models\RayAreaPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RayAreaPriceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/ray-area-prices",
     *     summary="List all ray area prices (Admin only)",
     *     description="Retrieve a list of all ray area prices for admin management.",
     *     tags={"Admin - Ray Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Ray area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="ray_area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ray_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="ray", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chest X-Ray")
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
        $rayAreaPrices = RayAreaPrice::with(['ray:id,name', 'area:id,name'])
            ->orderBy('ray_id')
            ->orderBy('area_id')
            ->get();

        return response()->json(['ray_area_prices' => $rayAreaPrices], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/ray-area-prices",
     *     summary="Create ray area price (Admin only)",
     *     description="Create a new ray area price for region-based pricing.",
     *     tags={"Admin - Ray Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ray_id","area_id","price"},
     *             @OA\Property(property="ray_id", type="integer", example=1, description="Ray ID"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="Area ID"),
     *             @OA\Property(property="price", type="number", format="float", example=100.00, description="Price for this ray in this area")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ray area price created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ray area price created successfully."),
     *             @OA\Property(property="ray_area_price", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ray_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00)
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
            'ray_id' => 'required|exists:rays,id',
            'area_id' => 'required|exists:areas,id',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if this ray-area combination already exists
        $existingPrice = RayAreaPrice::where('ray_id', $request->ray_id)
            ->where('area_id', $request->area_id)
            ->first();

        if ($existingPrice) {
            return response()->json([
                'message' => 'A price for this ray and area combination already exists. Use update instead.'
            ], 422);
        }

        $rayAreaPrice = RayAreaPrice::create([
            'ray_id' => $request->ray_id,
            'area_id' => $request->area_id,
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Ray area price created successfully.',
            'ray_area_price' => $rayAreaPrice
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/ray-area-prices/{id}",
     *     summary="Update ray area price (Admin only)",
     *     description="Update an existing ray area price.",
     *     tags={"Admin - Ray Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ray area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price"},
     *             @OA\Property(property="price", type="number", format="float", example=120.00, description="Updated price for this ray in this area")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ray area price updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ray area price updated successfully."),
     *             @OA\Property(property="ray_area_price", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ray_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ray area price not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $rayAreaPrice = RayAreaPrice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rayAreaPrice->update([
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Ray area price updated successfully.',
            'ray_area_price' => $rayAreaPrice
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/ray-area-prices/{id}",
     *     summary="Delete ray area price (Admin only)",
     *     description="Delete a ray area price.",
     *     tags={"Admin - Ray Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ray area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ray area price deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ray area price deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ray area price not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $rayAreaPrice = RayAreaPrice::findOrFail($id);
        $rayAreaPrice->delete();

        return response()->json([
            'message' => 'Ray area price deleted successfully.'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/ray-area-prices/ray/{rayId}",
     *     summary="Get prices for a specific ray (Admin only)",
     *     description="Retrieve all area prices for a specific ray.",
     *     tags={"Admin - Ray Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="rayId",
     *         in="path",
     *         required=true,
     *         description="Ray ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ray area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="ray", type="object"),
     *             @OA\Property(property="area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="area", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Beirut")
     *                 )
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ray not found"
     *     )
     * )
     */
    public function getRayPrices($rayId)
    {
        $ray = Ray::findOrFail($rayId);
        $areaPrices = RayAreaPrice::where('ray_id', $rayId)
            ->with('area:id,name')
            ->get();

        return response()->json([
            'ray' => $ray,
            'area_prices' => $areaPrices
        ], 200);
    }
}

