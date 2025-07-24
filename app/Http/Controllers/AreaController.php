<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

/**
 * Area Controller
 * 
 * Manages area/region data for region-based pricing
 */
class AreaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/areas",
     *     summary="List all areas",
     *     description="Retrieve a list of all available areas for user registration and profile updates.",
     *     tags={"Areas"},
     *     @OA\Response(
     *         response=200,
     *         description="Areas list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="areas", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $areas = Area::orderBy('name')->get();
        return response()->json(['areas' => $areas], 200);
    }
}