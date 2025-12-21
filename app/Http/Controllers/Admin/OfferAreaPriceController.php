<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Area;
use App\Models\OfferAreaPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OfferAreaPriceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/offer-area-prices",
     *     summary="List all offer area prices (Admin only)",
     *     description="Retrieve a list of all offer area prices for admin management.",
     *     tags={"Admin - Offer Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Offer area prices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="offer_area_prices", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="offer_id", type="integer", example=1),
     *                 @OA\Property(property="area_id", type="integer", example=1),
     *                 @OA\Property(property="offer_price", type="number", format="float", example=10.00),
     *                 @OA\Property(property="old_price", type="number", format="float", example=20.00),
     *                 @OA\Property(property="offer", type="object"),
     *                 @OA\Property(property="area", type="object")
     *             ))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $prices = OfferAreaPrice::with(['offer:id,name', 'area:id,name'])
            ->orderBy('offer_id')
            ->orderBy('area_id')
            ->get();

        return response()->json(['offer_area_prices' => $prices], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/offer-area-prices",
     *     summary="Create offer area price (Admin only)",
     *     description="Create a new offer area price for region-based pricing.",
     *     tags={"Admin - Offer Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"offer_id","area_id","offer_price","old_price"},
     *             @OA\Property(property="offer_id", type="integer", example=1),
     *             @OA\Property(property="area_id", type="integer", example=1),
     *             @OA\Property(property="offer_price", type="number", format="float", example=10.00),
     *             @OA\Property(property="old_price", type="number", format="float", example=20.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Offer area price created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required|exists:offers,id',
            'area_id' => 'required|exists:areas,id',
            'offer_price' => 'required|numeric|min:0',
            'old_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existingPrice = OfferAreaPrice::where('offer_id', $request->offer_id)
            ->where('area_id', $request->area_id)
            ->first();

        if ($existingPrice) {
            return response()->json([
                'message' => 'A price for this offer and area combination already exists. Use update instead.'
            ], 422);
        }

        $price = OfferAreaPrice::create([
            'offer_id' => $request->offer_id,
            'area_id' => $request->area_id,
            'offer_price' => $request->offer_price,
            'old_price' => $request->old_price,
        ]);

        return response()->json([
            'message' => 'Offer area price created successfully.',
            'offer_area_price' => $price
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/offer-area-prices/{id}",
     *     summary="Update offer area price (Admin only)",
     *     description="Update an existing offer area price.",
     *     tags={"Admin - Offer Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Offer area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"offer_price","old_price"},
     *             @OA\Property(property="offer_price", type="number", format="float", example=12.00),
     *             @OA\Property(property="old_price", type="number", format="float", example=25.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer area price updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $price = OfferAreaPrice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'offer_price' => 'required|numeric|min:0',
            'old_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $price->update([
            'offer_price' => $request->offer_price,
            'old_price' => $request->old_price,
        ]);

        return response()->json([
            'message' => 'Offer area price updated successfully.',
            'offer_area_price' => $price
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/offer-area-prices/{id}",
     *     summary="Delete offer area price (Admin only)",
     *     description="Delete an offer area price.",
     *     tags={"Admin - Offer Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Offer area price ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer area price deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $price = OfferAreaPrice::findOrFail($id);
        $price->delete();

        return response()->json([
            'message' => 'Offer area price deleted successfully.'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/offer-area-prices/offer/{offerId}",
     *     summary="Get prices for a specific offer (Admin only)",
     *     description="Retrieve all area prices for a specific offer.",
     *     tags={"Admin - Offer Region Pricing"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="offerId",
     *         in="path",
     *         required=true,
     *         description="Offer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer area prices retrieved successfully"
     *     )
     * )
     */
    public function getOfferPrices($offerId)
    {
        $offer = Offer::findOrFail($offerId);
        $areaPrices = OfferAreaPrice::where('offer_id', $offerId)
            ->with('area:id,name')
            ->get();

        return response()->json([
            'offer' => $offer,
            'area_prices' => $areaPrices
        ], 200);
    }
}

