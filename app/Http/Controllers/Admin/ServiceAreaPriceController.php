<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Area;
use App\Models\ServiceAreaPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceAreaPriceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/service-area-prices",
     *     summary="List all service area prices (Admin only)",
     *     description="Retrieve a list of all service area prices for admin management.",
     *     tags={"Admin - Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Service area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="service_area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="service_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="service", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Home Nursing")
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
        $serviceAreaPrices = ServiceAreaPrice::with(['service:id,name', 'area:id,name'])
            ->orderBy('service_id')
            ->orderBy('area_id')
            ->get();

        return response()->json(['service_area_prices' => $serviceAreaPrices], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/service-area-prices",
     *     summary="Create service area price (Admin only)",
     *     description="Create a new service area price for region-based pricing.",
     *     tags={"Admin - Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"service_id","area_id","price"},
     *             @OA\Property(property="service_id", type="integer", example=1, description="Service ID"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="Area ID"),
     *             @OA\Property(property="price", type="number", format="float", example=100.00, description="Price for this service in this area")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service area price created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service area price created successfully."),
     *             @OA\Property(property="service_area_price", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="service_id", type="integer", example=1),
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
            'service_id' => 'required|exists:services,id',
            'area_id' => 'required|exists:areas,id',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if this service-area combination already exists
        $existingPrice = ServiceAreaPrice::where('service_id', $request->service_id)
            ->where('area_id', $request->area_id)
            ->first();

        if ($existingPrice) {
            return response()->json([
                'message' => 'A price for this service and area combination already exists. Use update instead.'
            ], 422);
        }

        $serviceAreaPrice = ServiceAreaPrice::create([
            'service_id' => $request->service_id,
            'area_id' => $request->area_id,
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Service area price created successfully.',
            'service_area_price' => $serviceAreaPrice
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/service-area-prices/{id}",
     *     summary="Update service area price (Admin only)",
     *     description="Update an existing service area price.",
     *     tags={"Admin - Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price"},
     *             @OA\Property(property="price", type="number", format="float", example=120.00, description="New price for this service in this area")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service area price updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service area price updated successfully."),
     *             @OA\Property(property="service_area_price", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="service_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service area price not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $serviceAreaPrice = ServiceAreaPrice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serviceAreaPrice->update([
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Service area price updated successfully.',
            'service_area_price' => $serviceAreaPrice
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/service-area-prices/{id}",
     *     summary="Delete service area price (Admin only)",
     *     description="Delete a service area price.",
     *     tags={"Admin - Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service area price deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service area price deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service area price not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $serviceAreaPrice = ServiceAreaPrice::findOrFail($id);
        $serviceAreaPrice->delete();

        return response()->json([
            'message' => 'Service area price deleted successfully.'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/service-area-prices/service/{serviceId}",
     *     summary="Get prices for a specific service (Admin only)",
     *     description="Retrieve all area prices for a specific service.",
     *     tags={"Admin - Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="serviceId",
     *         in="path",
     *         required=true,
     *         description="Service ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="service", type="object"),
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
     *         description="Service not found"
     *     )
     * )
     */
    public function getServicePrices($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $areaPrices = ServiceAreaPrice::where('service_id', $serviceId)
            ->with('area:id,name')
            ->get();

        return response()->json([
            'service' => $service,
            'area_prices' => $areaPrices
        ], 200);
    }
}