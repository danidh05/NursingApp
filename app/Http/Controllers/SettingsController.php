<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Settings",
 *     description="Application settings management"
 * )
 */
class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @OA\Get(
     *     path="/api/settings",
     *     summary="Get all settings",
     *     tags={"Settings"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="job_application_url"),
     *                 @OA\Property(property="value", type="string", example="https://example.com/jobs"),
     *                 @OA\Property(property="type", type="string", example="url"),
     *                 @OA\Property(property="description", type="string", example="URL for job application redirect"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $settings = $this->settingsService->getAll();
        
        return response()->json([
            'data' => $settings
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/public",
     *     summary="Get public settings (for frontend)",
     *     tags={"Settings"},
     *     @OA\Response(
     *         response=200,
     *         description="Public settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="job_application_url", type="string", example="https://example.com/jobs"),
     *             @OA\Property(property="whatsapp_support_number", type="string", example="+1234567890")
     *         )
     *     )
     * )
     */
    public function getPublic(): JsonResponse
    {
        $settings = $this->settingsService->getPublicSettings();
        
        return response()->json($settings);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/{id}",
     *     summary="Get a specific setting",
     *     tags={"Settings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Setting retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="job_application_url"),
     *                 @OA\Property(property="value", type="string", example="https://example.com/jobs"),
     *                 @OA\Property(property="type", type="string", example="url"),
     *                 @OA\Property(property="description", type="string", example="URL for job application redirect"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Setting not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $setting = Settings::find($id);
        
        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }
        
        return response()->json(['data' => $setting]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings",
     *     summary="Create a new setting",
     *     tags={"Settings"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "value"},
     *             @OA\Property(property="key", type="string", example="job_application_url"),
     *             @OA\Property(property="value", type="string", example="https://example.com/jobs"),
     *             @OA\Property(property="type", type="string", example="url"),
     *             @OA\Property(property="description", type="string", example="URL for job application redirect"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Setting created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="job_application_url"),
     *                 @OA\Property(property="value", type="string", example="https://example.com/jobs"),
     *                 @OA\Property(property="type", type="string", example="url"),
     *                 @OA\Property(property="description", type="string", example="URL for job application redirect"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
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
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'required|string',
            'type' => 'string|in:string,url,phone,email,number,boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $setting = $this->settingsService->create($request->all());
        
        return response()->json(['data' => $setting], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/{id}",
     *     summary="Update a setting",
     *     tags={"Settings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string", example="https://new-example.com/jobs"),
     *             @OA\Property(property="type", type="string", example="url"),
     *             @OA\Property(property="description", type="string", example="Updated URL for job application redirect"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Setting updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="job_application_url"),
     *                 @OA\Property(property="value", type="string", example="https://new-example.com/jobs"),
     *                 @OA\Property(property="type", type="string", example="url"),
     *                 @OA\Property(property="description", type="string", example="Updated URL for job application redirect"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Setting not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'value' => 'required|string',
            'type' => 'string|in:string,url,phone,email,number,boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $setting = $this->settingsService->update($id, $request->all());
        
        return response()->json(['data' => $setting]);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/{id}",
     *     summary="Delete a setting",
     *     tags={"Settings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Setting deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Setting deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Setting not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->settingsService->delete($id);
        
        return response()->json(['message' => 'Setting deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/{id}/toggle",
     *     summary="Toggle setting active status",
     *     tags={"Settings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Setting status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="job_application_url"),
     *                 @OA\Property(property="value", type="string", example="https://example.com/jobs"),
     *                 @OA\Property(property="type", type="string", example="url"),
     *                 @OA\Property(property="description", type="string", example="URL for job application redirect"),
     *                 @OA\Property(property="is_active", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Setting not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function toggleActive(int $id): JsonResponse
    {
        $setting = $this->settingsService->toggleActive($id);
        
        return response()->json(['data' => $setting]);
    }
}