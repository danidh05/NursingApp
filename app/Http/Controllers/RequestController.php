<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Services\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Request Controller - 4-Stage Order Tracking System
 * 
 * This controller implements a comprehensive order tracking system with the following stages:
 * 1. submitted - User creates a new request
 * 2. assigned - Admin accepts request and assigns a nurse (starts time countdown)
 * 3. in_progress - Nurse arrives at location (automatic when time_needed_to_arrive reaches 0)
 * 4. completed - Service is finished and request is closed
 * 5. canceled - Request was cancelled
 * 
 * Key Features:
 * - Real-time time_needed_to_arrive countdown (cached, not in database)
 * - Automatic status progression when nurse arrives
 * - Location data from user profile (latitude/longitude)
 * - Optional request name/title field
 * - Comprehensive event-driven status updates
 */
class RequestController extends Controller
{
    public function __construct(
        private RequestService $requestService
    ) {
        // Remove the authorizeResource() call - it's not needed in Laravel 11
        // $this->authorizeResource(Request::class);
    }

    /**
     * @OA\Get(
     *     path="/api/requests",
     *     summary="List all requests with 4-stage tracking",
     *     description="Retrieve all requests with comprehensive order tracking. Users see only their own requests, admins see all requests. Latitude/longitude come from user location info, time_needed_to_arrive is cached and decreases over time.",
     *     tags={"Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Requests retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="name", type="string", example="Emergency Home Care", nullable=true, description="Optional request title/name"),
     *                 @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent"),
     *                 @OA\Property(property="status", type="string", example="submitted", enum={"submitted","assigned","in_progress","completed","canceled"}, description="4-stage order tracking: submitted → assigned → in_progress → completed"),
     *                 @OA\Property(property="nurse_gender", type="string", example="female", enum={"male","female","any"}),
     *                 @OA\Property(property="time_type", type="string", example="full-time", enum={"full-time","part-time"}),
     *                 @OA\Property(property="scheduled_time", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="location", type="string", example="123 Main St, New York"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.7128, nullable=true, description="From user location info, not stored in requests table"),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.0060, nullable=true, description="From user location info, not stored in requests table"),
     *                 @OA\Property(property="time_needed_to_arrive", type="integer", example=30, nullable=true, description="Cached time in minutes, decreases over time, auto-updates status to in_progress when reaches 0"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="services", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Home Nursing"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00)
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $requests = $this->requestService->getAllRequests($user);
    
        return response()->json($requests);
    }
    
    /**
     * @OA\Post(
     *     path="/api/requests",
     *     summary="Create a new request",
     *     description="Create a new service request. Only accessible by users.",
     *     tags={"Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name","phone_number","problem_description","service_ids","location","latitude","longitude"},
     *             @OA\Property(property="full_name", type="string", example="John Doe", description="Full name of the person needing care"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="Contact phone number"),
     *             @OA\Property(property="name", type="string", example="Emergency Home Care", description="Optional request name/title"),
     *             @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent", description="Description of the care needed"),
     *             @OA\Property(property="service_ids", type="array", @OA\Items(type="integer"), example={1,2}, description="Array of service IDs"),
     *             @OA\Property(property="nurse_gender", type="string", example="female", enum={"male","female","any"}, description="Preferred nurse gender"),
     *             @OA\Property(property="time_type", type="string", example="full-time", enum={"full-time","part-time"}, description="Type of time commitment needed"),
     *             @OA\Property(property="scheduled_time", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Scheduled time for service"),
     *             @OA\Property(property="location", type="string", example="123 Main St, New York", description="Service location address"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128, description="Latitude coordinate"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060, description="Longitude coordinate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="name", type="string", example="Emergency Home Care", nullable=true),
     *             @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent"),
     *             @OA\Property(property="status", type="string", example="submitted", enum={"submitted","assigned","in_progress","completed","canceled"}),
     *             @OA\Property(property="nurse_gender", type="string", example="female"),
     *             @OA\Property(property="time_type", type="string", example="full-time"),
     *             @OA\Property(property="scheduled_time", type="string", format="date-time"),
     *             @OA\Property(property="location", type="string", example="123 Main St, New York"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User role required"
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
    public function store(HttpRequest $httpRequest): JsonResponse
    {
        $validated = $httpRequest->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'name' => 'nullable|string|max:255',
            'problem_description' => 'required|string',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'nurse_gender' => 'nullable|string|in:male,female,any',
            'time_type' => 'nullable|string|in:full-time,part-time',
            'scheduled_time' => 'nullable|date|after:now',
            'location' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $request = $this->requestService->createRequest($validated, Auth::user());
        
        return response()->json($request, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/requests/{id}",
     *     summary="Get request details with order tracking",
     *     description="Retrieve details of a specific request with 4-stage order tracking. Users can only view their own requests, admins can view any request. Real-time time_needed_to_arrive countdown and automatic status progression.",
     *     tags={"Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Request ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Request details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="name", type="string", example="Emergency Home Care", nullable=true, description="Optional request title/name"),
     *             @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent"),
     *             @OA\Property(property="status", type="string", example="assigned", enum={"submitted","assigned","in_progress","completed","canceled"}, description="Current stage in 4-stage tracking system"),
     *             @OA\Property(property="nurse_gender", type="string", example="female", enum={"male","female","any"}),
     *             @OA\Property(property="time_type", type="string", example="full-time", enum={"full-time","part-time"}),
     *             @OA\Property(property="scheduled_time", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *             @OA\Property(property="location", type="string", example="123 Main St, New York"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128, nullable=true, description="From user's location settings"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060, nullable=true, description="From user's location settings"),
     *             @OA\Property(property="time_needed_to_arrive", type="integer", example=30, nullable=true, description="Live countdown in minutes, auto-triggers status change to in_progress when reaches 0"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             )),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.7128, nullable=true),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.0060, nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Cannot access this request"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Request not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $requestData = $this->requestService->getRequest($id, $user);
        
        return response()->json($requestData);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/requests/{id}",
     *     summary="Update request (Admin only)",
     *     description="Update a request. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Request ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="full_name", type="string", example="John Doe", description="Full name of the person needing care"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="Contact phone number"),
     *             @OA\Property(property="name", type="string", example="Emergency Home Care", description="Optional request name/title"),
     *             @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent", description="Description of the care needed"),
     *             @OA\Property(property="status", type="string", example="assigned", enum={"submitted","assigned","in_progress","completed","canceled"}, description="Request status"),
     *             @OA\Property(property="time_needed_to_arrive", type="integer", example=30, description="Time in minutes needed to arrive"),
     *             @OA\Property(property="nurse_gender", type="string", example="female", enum={"male","female","any"}, description="Preferred nurse gender"),
     *             @OA\Property(property="time_type", type="string", example="full-time", enum={"full-time","part-time"}, description="Type of time commitment needed"),
     *             @OA\Property(property="scheduled_time", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Scheduled time for service")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Request updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="name", type="string", example="Emergency Home Care", nullable=true),
     *             @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent"),
     *             @OA\Property(property="status", type="string", example="assigned"),
     *             @OA\Property(property="nurse_gender", type="string", example="female"),
     *             @OA\Property(property="time_type", type="string", example="full-time"),
     *             @OA\Property(property="scheduled_time", type="string", format="date-time"),
     *             @OA\Property(property="location", type="string", example="123 Main St, New York"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *             @OA\Property(property="time_needed_to_arrive", type="integer", example=30),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Request not found"
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
    public function update(HttpRequest $httpRequest, int $id): JsonResponse
    {
        $user = Auth::user();
        
        // Debug: Log the update attempt
        \Log::info("RequestController::update called for request ID: {$id} by user: {$user->id} with role: {$user->role->name}");
        
        $validated = $httpRequest->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'name' => 'sometimes|nullable|string|max:255',
            'problem_description' => 'sometimes|string',
            'status' => 'sometimes|string|in:submitted,assigned,in_progress,completed,canceled',
            'time_needed_to_arrive' => 'sometimes|integer|min:0',
            'nurse_gender' => 'sometimes|string|in:male,female,any',
            'time_type' => 'sometimes|string|in:full-time,part-time',
            'scheduled_time' => 'sometimes|date|after:now',
        ]);

        // Debug: Log the validated data
        \Log::info("Validated data: " . json_encode($validated));

        $updatedRequest = $this->requestService->updateRequest($id, $validated, $user);

        return response()->json($updatedRequest);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/requests/{id}",
     *     summary="Soft delete request (Admin only)",
     *     description="Soft delete a request. The request is removed from admin view but remains available to users. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Request ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Request soft deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Request removed from admin view, but still available to users.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Request not found"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $this->requestService->softDeleteRequest($id, $user);
    
        return response()->json([
            'message' => 'Request removed from admin view, but still available to users.'
        ]);
    }
}