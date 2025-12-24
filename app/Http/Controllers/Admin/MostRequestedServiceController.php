<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MostRequestedService;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - Most Requested Services",
 *     description="API Endpoints for managing Most Requested Services (Admin only)"
 * )
 */
class MostRequestedServiceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/most-requested-services",
     *     summary="List all most requested services",
     *     tags={"Admin - Most Requested Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        
        $mostRequested = MostRequestedService::with(['service.areaPrices.area', 'service.category'])
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $mostRequested->map(function ($item) use ($locale) {
                $service = $item->service;
                $translation = $service ? $service->translate($locale) : null;
                return [
                    'id' => $item->id,
                    'service_id' => $item->service_id,
                    'order' => $item->order,
                    'service' => $service ? [
                        'id' => $service->id,
                        'name' => $translation?->name ?? $service->name,
                        'image' => $service->image_url,
                        'price' => $service->price,
                        'description' => $translation?->description ?? $service->description,
                        'category_id' => $service->category_id,
                    ] : null,
                ];
            }),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/most-requested-services",
     *     summary="Add a service to most requested",
     *     tags={"Admin - Most Requested Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"service_id"},
     *             @OA\Property(property="service_id", type="integer", example=1),
     *             @OA\Property(property="order", type="integer", example=0, description="Display order (optional)")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id|unique:most_requested_services,service_id',
            'order' => 'nullable|integer|min:0',
        ]);

        $mostRequested = MostRequestedService::create([
            'service_id' => $validated['service_id'],
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service added to most requested',
            'data' => $mostRequested,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/most-requested-services/{id}",
     *     summary="Update most requested service order",
     *     tags={"Admin - Most Requested Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="integer", example=1, description="Display order"),
     *             @OA\Property(property="service_id", type="integer", example=2, description="Service ID (optional, to change service)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function update(Request $request, MostRequestedService $mostRequestedService): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id|unique:most_requested_services,service_id,' . $mostRequestedService->id,
            'order' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['service_id'])) {
            $mostRequestedService->service_id = $validated['service_id'];
        }
        if (isset($validated['order'])) {
            $mostRequestedService->order = $validated['order'];
        }
        $mostRequestedService->save();

        return response()->json([
            'success' => true,
            'message' => 'Most requested service updated',
            'data' => $mostRequestedService,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/most-requested-services/{id}",
     *     summary="Remove service from most requested",
     *     tags={"Admin - Most Requested Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function destroy(MostRequestedService $mostRequestedService): JsonResponse
    {
        $mostRequestedService->delete();
        return response()->json([
            'success' => true,
            'message' => 'Service removed from most requested',
        ]);
    }
}
