<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use App\Services\AreaService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Admin - Areas",
 *     description="API Endpoints for Area management (Admin only)"
 * )
 */
class AreaController extends Controller
{
    public function __construct(
        private AreaService $areaService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/areas",
     *     summary="Get all areas with user counts",
     *     description="Retrieve all areas with user count information for admin management",
     *     tags={"Admin - Areas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all areas with user counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Areas retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown"),
     *                 @OA\Property(property="users_count", type="integer", example=5),
     *                 @OA\Property(property="service_prices_count", type="integer", example=3),
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
    public function index(): JsonResponse
    {
        $areas = $this->areaService->getAreasWithUserCount();
        
        return response()->json([
            'success' => true,
            'message' => 'Areas retrieved successfully',
            'data' => $areas->map(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'users_count' => $area->users_count,
                'service_prices_count' => $area->service_prices_count,
                'created_at' => $area->created_at->toISOString(),
                'updated_at' => $area->updated_at->toISOString(),
            ])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/areas",
     *     summary="Create a new area",
     *     description="Create a new area for user registration and service pricing",
     *     tags={"Admin - Areas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Downtown", description="The area name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Area created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
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
    public function store(StoreAreaRequest $request): JsonResponse
    {
        $areaDTO = $this->areaService->createArea($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Area created successfully',
            'data' => $areaDTO->toArray()
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/areas/{id}",
     *     summary="Get a specific area with details",
     *     description="Retrieve a specific area with user and service price details",
     *     tags={"Admin - Areas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Area ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area details with user and service price information",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="area", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Downtown"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 ),
     *                 @OA\Property(property="user_count", type="integer", example=5),
     *                 @OA\Property(property="service_price_count", type="integer", example=3),
     *                 @OA\Property(property="users", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="role", type="string", example="user"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="service_prices", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="service_name", type="string", example="Home Care"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
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
    public function show(int $id): JsonResponse
    {
        $areaDetails = $this->areaService->getAreaWithDetails($id);
        
        if (!$areaDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Area retrieved successfully',
            'data' => $areaDetails
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/areas/{id}",
     *     summary="Update an area",
     *     description="Update an existing area name",
     *     tags={"Admin - Areas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Area ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Downtown Updated", description="The area name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Downtown Updated"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
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
    public function update(UpdateAreaRequest $request, int $id): JsonResponse
    {
        $areaDTO = $this->areaService->updateArea($id, $request->validated());
        
        if (!$areaDTO) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Area updated successfully',
            'data' => $areaDTO->toArray()
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/areas/{id}",
     *     summary="Delete an area",
     *     description="Delete an area if it has no users or service prices assigned",
     *     tags={"Admin - Areas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Area ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete area with users or service prices",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete area that has users assigned to it.")
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
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->areaService->deleteArea($id);
            
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Area deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 