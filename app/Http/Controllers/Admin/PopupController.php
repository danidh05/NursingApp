<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePopupRequest;
use App\Http\Requests\UpdatePopupRequest;
use App\Services\PopupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PopupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PopupService $popupService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/popups",
     *     summary="List all popups (Admin)",
     *     description="Retrieve all popups for admin management. Accessible only to administrators.",
     *     tags={"Admin - Popups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Popups retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="popups", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", example="https://firebasestorage.googleapis.com/v0/b/.../popup-images/image.jpg"),
     *                 @OA\Property(property="title", type="string", example="Welcome to Our App"),
     *                 @OA\Property(property="content", type="string", example="We are excited to announce our new features..."),
     *                 @OA\Property(property="type", type="string", enum={"info","warning","promo"}, example="info"),
     *                 @OA\Property(property="start_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="end_date", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
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
        $this->authorize('viewAny', \App\Models\Popup::class);
        
        $popups = $this->popupService->getAllPopups();
        return response()->json(['popups' => $popups], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/popups",
     *     summary="Create a new popup (Admin)",
     *     description="Create a new popup with image upload and scheduling options. Accessible only to administrators.",
     *     tags={"Admin - Popups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Popup creation data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image", "title", "content", "type"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Popup image file (JPG, JPEG, PNG, max 2MB)"),
     *                 @OA\Property(property="title", type="string", example="Welcome to Our App", maxLength=255),
     *                 @OA\Property(property="content", type="string", example="We are excited to announce our new features and improvements."),
     *                 @OA\Property(property="type", type="string", enum={"info","warning","promo"}, example="info", description="Popup type"),
     *                 @OA\Property(property="start_date", type="string", format="date-time", nullable=true, example="2024-01-15T10:00:00Z", description="When popup becomes active (null = immediately)"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-01-30T10:00:00Z", description="When popup expires (null = never)"),
     *                 @OA\Property(property="is_active", type="boolean", example=true, description="Whether popup is enabled")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Popup created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Popup created successfully."),
     *             @OA\Property(property="popup", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="end_date", type="string", format="date-time"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
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
    public function store(StorePopupRequest $request)
    {
        $this->authorize('create', \App\Models\Popup::class);
        
        try {
            $popup = $this->popupService->createPopup($request->validated());
            
            return response()->json([
                'message' => 'Popup created successfully.',
                'popup' => $popup
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create popup: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/popups/{id}",
     *     summary="Get specific popup (Admin)",
     *     description="Retrieve a specific popup by ID. Accessible only to administrators.",
     *     tags={"Admin - Popups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Popup ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Popup retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="popup", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="end_date", type="string", format="date-time"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Popup not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Popup not found")
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
    public function show(int $id)
    {
        try {
            $popup = $this->popupService->getPopup($id);
            $this->authorize('view', $popup);
            
            return response()->json(['popup' => $popup], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Popup not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve popup: ' . $e->getMessage()], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/popups/{id}",
     *     summary="Update popup (Admin)",
     *     description="Update an existing popup. Image upload is optional. Accessible only to administrators.",
     *     tags={"Admin - Popups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Popup ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Popup update data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "content", "type"},
     *                 @OA\Property(property="image", type="string", format="binary", description="New popup image file (optional, JPG, JPEG, PNG, max 2MB)", nullable=true),
     *                 @OA\Property(property="title", type="string", example="Updated Welcome Message", maxLength=255),
     *                 @OA\Property(property="content", type="string", example="Updated content with new information."),
     *                 @OA\Property(property="type", type="string", enum={"info","warning","promo"}, example="promo", description="Popup type"),
     *                 @OA\Property(property="start_date", type="string", format="date-time", nullable=true, example="2024-01-15T10:00:00Z", description="When popup becomes active"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-01-30T10:00:00Z", description="When popup expires"),
     *                 @OA\Property(property="is_active", type="boolean", example=false, description="Whether popup is enabled")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Popup updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Popup updated successfully."),
     *             @OA\Property(property="popup", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="end_date", type="string", format="date-time"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Popup not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Popup not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
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
    public function update(UpdatePopupRequest $request, int $id)
    {
        try {
            $popup = $this->popupService->getPopup($id);
            $this->authorize('update', $popup);
            
            $popup = $this->popupService->updatePopup($id, $request->validated());
            
            return response()->json([
                'message' => 'Popup updated successfully.',
                'popup' => $popup
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Popup not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update popup: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/popups/{id}",
     *     summary="Delete popup (Admin)",
     *     description="Delete a popup and its associated image from Firebase. Accessible only to administrators.",
     *     tags={"Admin - Popups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Popup ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Popup deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Popup deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Popup not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Popup not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Failed to delete popup",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to delete popup: [error details]")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        try {
            $popup = $this->popupService->getPopup($id);
            $this->authorize('delete', $popup);
            
            $this->popupService->deletePopup($id);
            
            return response()->json([
                'message' => 'Popup deleted successfully.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Popup not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete popup: ' . $e->getMessage()
            ], 422);
        }
    }
}