<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Services\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CreateRequestRequest;

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
     *     summary="List all requests with 4-stage tracking and filtering",
     *     description="Retrieve all requests with comprehensive order tracking. Users see only their own requests, admins see all requests. Supports filtering by status and insurance requests. Latitude/longitude come from user location info, time_needed_to_arrive is cached and decreases over time.",
     *     tags={"Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by request status",
     *         @OA\Schema(type="string", enum={"submitted","assigned","in_progress","completed","canceled"}, example="submitted")
     *     ),
     *     @OA\Parameter(
     *         name="request_with_insurance",
     *         in="query",
     *         required=false,
     *         description="Filter by insurance requests (true/false). Only applies to Category 2 (Tests) requests.",
     *         @OA\Schema(type="string", enum={"true","false","1","0"}, example="true")
     *     ),
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
     *                 @OA\Property(property="thread_id", type="integer", example=123, nullable=true, description="Chat thread ID for admin-user communication"),
     *                 @OA\Property(property="total_price", type="number", format="float", example=150.00, description="Price calculated based on request area"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="area", type="object", description="Area information for the request",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Beirut")
     *                 ),
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
    public function index(HttpRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        // Get filter parameters
        $filters = [
            'status' => $request->query('status'),
            'request_with_insurance' => $request->query('request_with_insurance'),
        ];
        
        $requests = $this->requestService->getAllRequests($user, $filters);
    
        return response()->json($requests);
    }
    
    /**
     * @OA\Get(
     *     path="/api/requests/default-area",
     *     summary="Get user's default area for request creation",
     *     description="Get the user's registered area to use as default in the request creation form. This allows the frontend to pre-select the user's area while still allowing them to change it.",
     *     tags={"Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Default area retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="default_area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getDefaultArea(): JsonResponse
    {
        $user = Auth::user();
        $defaultArea = null;
        
        if ($user->area_id) {
            $area = \App\Models\Area::find($user->area_id);
            if ($area) {
                $defaultArea = [
                    'id' => $area->id,
                    'name' => $area->name,
                ];
            }
        }
        
        return response()->json(['default_area' => $defaultArea]);
    }

    /**
     * @OA\Post(
     *     path="/api/requests",
     *     summary="Create a new request or multiple requests",
     *     description="Create one or multiple service requests. Supports multiple categories with different payloads. Can accept either: 1) A single request object (multipart/form-data or JSON), or 2) An array of request objects (JSON only). For single requests with file uploads, use multipart/form-data. For multiple requests or JSON-only single requests, use application/json. Only accessible by users.",
     *     tags={"Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="For single request: Use multipart/form-data (for file uploads) or application/json. For multiple requests: Use application/json with an array of request objects. Required fields vary by category_id. Boolean values should be sent as strings: 'true' or 'false'.",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 title="Create Request - All Categories",
     *                 description="Common fields for all categories. Category-specific required fields are listed below.",
     *                 @OA\Property(property="category_id", type="integer", example=1, description="REQUIRED: Category ID (1=Service Request, 2=Tests, 3=Rays, 4=Machines, 5=Physiotherapist, 6=Offers, 7=Duties, 8=Doctors). Defaults to 1 if not provided."),
     *                 @OA\Property(property="machine_id", type="integer", example=1, description="REQUIRED for Category 4 only: Machine ID"),
     *                 @OA\Property(property="from_date", type="string", format="date", example="2026-01-15", description="Optional for Category 4 only: Rental start date (YYYY-MM-DD)"),
     *                 @OA\Property(property="to_date", type="string", format="date", example="2026-01-20", description="Optional for Category 4 only: Rental end date (YYYY-MM-DD, must be after from_date)"),
     *                 @OA\Property(property="first_name", type="string", example="John", description="Optional: First name"),
     *                 @OA\Property(property="last_name", type="string", example="Doe", description="Optional: Last name"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe", description="Optional: Full name (can be built from first_name + last_name)"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890", description="Optional: Contact phone number"),
     *                 @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent", description="Optional: Description of the problem/care needed"),
     *                 @OA\Property(property="nurse_gender", type="string", example="female", enum={"male","female","any"}, description="Optional: Preferred nurse gender"),
     *                 @OA\Property(property="name", type="string", example="Emergency Home Care", description="Optional request name/title"),
     *                 @OA\Property(property="additional_information", type="string", example="Additional notes", description="Optional additional information for all categories"),
     *                 @OA\Property(property="use_saved_address", type="string", example="false", enum={"true","false","0","1"}, description="Flag to use saved user address. Send as string: 'true' or 'false'. If 'false', address fields are required for Category 1."),
     *                 @OA\Property(property="address_city", type="string", example="Beirut", description="City (required for Category 1 if use_saved_address is false, optional for other categories)"),
     *                 @OA\Property(property="address_street", type="string", example="Fouad Chehab Street", description="Street address (required for Category 1 if use_saved_address is false, optional for other categories)"),
     *                 @OA\Property(property="address_building", type="string", example="Hamood Center, 3rd floor", description="Building information (optional)"),
     *                 @OA\Property(property="address_additional_information", type="string", example="Apartment 5, ring the bell", description="Additional address information (optional)"),
     *                 @OA\Property(property="location", type="string", example="33.8938,35.5018", description="Location coordinates (latitude,longitude) or address string (optional)"),
     *                 
     *                 @OA\Property(property="service_id", type="integer", example=1, description="REQUIRED for Category 1 only: Single service ID"),
     *                 @OA\Property(property="area_id", type="integer", example=1, description="Optional for Category 1: Area ID for region-specific pricing. If not provided, uses user's registered area"),
     *                 @OA\Property(property="time_type", type="string", example="full-time", enum={"full-time","part-time"}, description="Optional for Category 1: Type of time commitment needed"),
     *                 @OA\Property(property="scheduled_time", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Optional for Category 1: For immediate requests: use now(). For scheduled: use future time"),
     *                 @OA\Property(property="ending_time", type="string", format="date-time", example="2024-01-15T12:00:00Z", description="Optional for Category 1: Required only for scheduled appointments (not immediate requests)"),
     *                 
     *                 @OA\Property(property="test_package_id", type="integer", example=1, description="REQUIRED for Category 2: Test package ID"),
     *                 @OA\Property(property="request_details_files[]", type="array", @OA\Items(type="string", format="binary"), description="Optional for Category 2: Array of files (PDF, JPG, PNG, max 5MB each) - x-rays, prescriptions, lab reports"),
     *                 @OA\Property(property="notes", type="string", example="Patient has allergies", description="Optional for Category 2: Notes"),
     *                 @OA\Property(property="request_with_insurance", type="string", example="true", enum={"true","false","0","1"}, description="Optional for Category 2: Request with insurance option. Send as string: 'true' or 'false'"),
     *                 @OA\Property(property="attach_front_face", type="string", format="binary", description="Optional for Category 2: Insurance card front face (PDF, JPG, PNG, max 5MB) - Required if request_with_insurance is 'true'"),
     *                 @OA\Property(property="attach_back_face", type="string", format="binary", description="Optional for Category 2: Insurance card back face (PDF, JPG, PNG, max 5MB) - Required if request_with_insurance is 'true'"),
     *                 
     *                 @OA\Property(property="physiotherapist_id", type="integer", example=1, description="REQUIRED for Category 5: Physiotherapist ID"),
     *                 @OA\Property(property="sessions_per_month", type="integer", example=8, description="REQUIRED for Category 5: Sessions per month"),
     *                 @OA\Property(property="machines_included", type="string", example="false", enum={"true","false","0","1"}, description="Optional for Category 5: Whether machines are included (send as string)"),
     *                 @OA\Property(property="physio_machines[]", type="array", @OA\Items(type="integer"), description="Optional for Category 5: Array of physio machine IDs"),
     *                 @OA\Property(property="request_details", type="string", format="binary", description="Optional for Category 5: Single PDF file (max 5MB)"),
     *                 
     *                 @OA\Property(property="nurse_visit_id", type="integer", example=1, description="REQUIRED for Category 7 (Nurse Visits subcategory): Nurse visit ID"),
     *                 @OA\Property(property="visits_per_day", type="integer", example=2, description="REQUIRED with nurse_visit_id: visits per day (1-4)"),
     *                 @OA\Property(property="duty_id", type="integer", example=1, description="REQUIRED for Category 7 (Duties subcategory): Duty ID"),
     *                 @OA\Property(property="duration_hours", type="integer", example=12, description="REQUIRED with duty_id unless is_continuous_care=true. Allowed: 4,6,8,12,24"),
     *                 @OA\Property(property="is_continuous_care", type="string", example="false", enum={"true","false","0","1"}, description="Category 7 (Duties): Continuous care (1 month). If true, duration_hours not required"),
     *                 @OA\Property(property="is_day_shift", type="string", example="true", enum={"true","false","0","1"}, description="Category 7 (Duties/Babysitter): Day shift (true) or night shift (false)"),
     *                 @OA\Property(property="babysitter_id", type="integer", example=1, description="REQUIRED for Category 7 (Babysitter subcategory): Babysitter ID"),
     *                 
     *                 
     *                 @OA\Property(property="doctor_id", type="integer", example=1, description="REQUIRED for Category 8: Doctor ID"),
     *                 @OA\Property(property="slot_id", type="integer", example=10, description="REQUIRED for Category 8: Availability slot ID"),
     *                 @OA\Property(property="appointment_type", type="string", example="check_at_home", enum={"check_at_home","check_at_clinic","video_call"}, description="REQUIRED for Category 8: Appointment type"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="category_id", type="integer", example=1, description="Category ID (defaults to 1: Service Request)"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="Area ID for region-specific pricing"),
     *             @OA\Property(property="first_name", type="string", example="John", nullable=true),
     *             @OA\Property(property="last_name", type="string", example="Doe", nullable=true),
     *             @OA\Property(property="full_name", type="string", example="John Doe", nullable=true),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="name", type="string", example="Emergency Home Care", nullable=true),
     *             @OA\Property(property="problem_description", type="string", example="Need nursing care for elderly parent"),
     *             @OA\Property(property="status", type="string", example="submitted", enum={"submitted","assigned","in_progress","completed","canceled"}),
     *             @OA\Property(property="nurse_gender", type="string", example="female"),
     *             @OA\Property(property="time_type", type="string", example="full-time"),
     *             @OA\Property(property="scheduled_time", type="string", format="date-time"),
     *             @OA\Property(property="location", type="string", example="33.8938,35.5018"),
     *             @OA\Property(property="use_saved_address", type="boolean", example=false),
     *             @OA\Property(property="address_city", type="string", example="Beirut", nullable=true),
     *             @OA\Property(property="address_street", type="string", example="Fouad Chehab Street", nullable=true),
     *             @OA\Property(property="address_building", type="string", example="Hamood Center, 3rd floor", nullable=true),
     *             @OA\Property(property="address_additional_information", type="string", example="Apartment 5", nullable=true),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128, nullable=true, description="From user location info"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060, nullable=true, description="From user location info"),
     *             @OA\Property(property="total_price", type="number", format="float", example=150.00, description="Price calculated based on selected area"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="area", type="object", description="Area information for the request",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
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
    public function store(CreateRequestRequest $httpRequest): JsonResponse
    {
        // Check if the request body is a JSON array
        $jsonContent = $httpRequest->getContent();
        $isJsonArray = false;
        $requestsData = null;
        
        if ($httpRequest->isJson() && !empty($jsonContent)) {
            $decoded = json_decode($jsonContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded[0])) {
                // It's a JSON array
                $isJsonArray = true;
                $requestsData = $decoded;
            }
        }
        
        // If it's an array, process each request
        if ($isJsonArray && is_array($requestsData)) {
            return $this->processMultipleRequests($requestsData);
        }
        
        // Otherwise, process as a single request (existing behavior)
        return $this->processSingleRequest($httpRequest);
    }
    
    /**
     * Process a single request (existing behavior).
     */
    private function processSingleRequest($httpRequest): JsonResponse
    {
        // DEBUG STEP 1: Check what files are in the raw request
        Log::info('=== REQUEST CONTROLLER: File Upload Debug Start ===');
        Log::info('Request has files: ' . ($httpRequest->hasFile('request_details_files') ? 'YES' : 'NO'));
        Log::info('Request has attach_front_face: ' . ($httpRequest->hasFile('attach_front_face') ? 'YES' : 'NO'));
        Log::info('Request has attach_back_face: ' . ($httpRequest->hasFile('attach_back_face') ? 'YES' : 'NO'));
        
        // Get all files from request (handles both 'request_details_files' and 'request_details_files[]')
        $allFiles = $httpRequest->allFiles();
        Log::info('All files keys in request: ' . json_encode(array_keys($allFiles)));
        
        // Get validated data (excludes files)
        $validated = $httpRequest->validated();
        Log::info('Validated data keys: ' . json_encode(array_keys($validated)));
        
        // For Category 2 and Category 3, merge file uploads back into validated data
        $categoryId = $httpRequest->input('category_id', 1);
        Log::info('Category ID: ' . $categoryId);
        
        if ($categoryId === 2 || $categoryId === 3 || $categoryId === 5 || $categoryId === 7) {
            // DEBUG STEP 2: Check each file field
            // IMPORTANT: Always extract files from allFiles() first, as validated() may have converted them
            // Handle request_details_files - Postman sends as 'request_details_files[]' but Laravel receives as 'request_details_files'
            // Also handle case where single file is sent (not array)
            $requestDetailsFiles = null;
            
            // ALWAYS get files from allFiles() first (these are guaranteed to be UploadedFile objects)
            if (isset($allFiles['request_details_files'])) {
                $requestDetailsFiles = $allFiles['request_details_files'];
                Log::info('Found request_details_files in allFiles() (without brackets)');
            } elseif (isset($allFiles['request_details_files[]'])) {
                $requestDetailsFiles = $allFiles['request_details_files[]'];
                Log::info('Found request_details_files[] in allFiles() (with brackets)');
            }
            
            // Only check validated data if not found in allFiles() (but this shouldn't happen)
            if ($requestDetailsFiles === null && isset($validated['request_details_files'])) {
                $validatedFile = $validated['request_details_files'];
                // Only use if it's an UploadedFile object
                if ($validatedFile instanceof \Illuminate\Http\UploadedFile || 
                    (is_array($validatedFile) && isset($validatedFile[0]) && $validatedFile[0] instanceof \Illuminate\Http\UploadedFile)) {
                    $requestDetailsFiles = $validatedFile;
                    Log::info('Found request_details_files in validated data (UploadedFile object)');
                } else {
                    Log::warning('request_details_files in validated data is NOT UploadedFile, type: ' . gettype($validatedFile));
                }
            }
            
            if ($requestDetailsFiles !== null) {
                // Ensure it's an array (handle both single file and array)
                if (!is_array($requestDetailsFiles)) {
                    $requestDetailsFiles = [$requestDetailsFiles];
                    Log::info('Normalized single file to array');
                }
                
                // Verify each file is an UploadedFile object
                $validFiles = [];
                foreach ($requestDetailsFiles as $index => $file) {
                    Log::info("request_details_files[$index] type: " . gettype($file));
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        Log::info("request_details_files[$index] is UploadedFile: " . $file->getClientOriginalName());
                        $validFiles[] = $file;
                    } else {
                        Log::warning("request_details_files[$index] is NOT UploadedFile, it's: " . gettype($file));
                    }
                }
                
                if (!empty($validFiles)) {
                    $validated['request_details_files'] = $validFiles;
                    Log::info('Added ' . count($validFiles) . ' valid files to request_details_files');
                } else {
                    Log::warning('No valid UploadedFile objects found in request_details_files');
                }
            } else {
                Log::warning('request_details_files not found in allFiles() or validated data');
            }
            
            // Handle attach_front_face
            if (isset($allFiles['attach_front_face'])) {
                $file = $allFiles['attach_front_face'];
                Log::info('attach_front_face type: ' . gettype($file));
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    Log::info('attach_front_face is UploadedFile: ' . $file->getClientOriginalName());
                    $validated['attach_front_face'] = $file;
                } else {
                    Log::warning('attach_front_face is NOT UploadedFile, it\'s: ' . gettype($file));
                    if (is_string($file)) {
                        Log::warning('attach_front_face is a string (temp path): ' . substr($file, 0, 100));
                    }
                }
            } else {
                Log::warning('attach_front_face not found in allFiles()');
            }
            
            // Handle attach_back_face
            if (isset($allFiles['attach_back_face'])) {
                $file = $allFiles['attach_back_face'];
                Log::info('attach_back_face type: ' . gettype($file));
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    Log::info('attach_back_face is UploadedFile: ' . $file->getClientOriginalName());
                    $validated['attach_back_face'] = $file;
                } else {
                    Log::warning('attach_back_face is NOT UploadedFile, it\'s: ' . gettype($file));
                    if (is_string($file)) {
                        Log::warning('attach_back_face is a string (temp path): ' . substr($file, 0, 100));
                    }
                }
            } else {
                Log::warning('attach_back_face not found in allFiles()');
            }
            
            Log::info('Final validated data keys after file merge: ' . json_encode(array_keys($validated)));
        }

        $request = $this->requestService->createRequest($validated, Auth::user());
        
        Log::info('=== REQUEST CONTROLLER: File Upload Debug End ===');
        
        return response()->json($request, 201);
    }
    
    /**
     * Process multiple requests from a JSON array.
     */
    private function processMultipleRequests(array $requestsData): JsonResponse
    {
        $results = [];
        $errors = [];
        
        foreach ($requestsData as $index => $requestData) {
            try {
                // Validate each request individually
                $validator = \Illuminate\Support\Facades\Validator::make($requestData, $this->getValidationRulesForRequest($requestData));
                
                if ($validator->fails()) {
                    $errors[] = [
                        'index' => $index,
                        'errors' => $validator->errors()->toArray(),
                    ];
                    continue;
                }
                
                // Process the validated request
                $validated = $validator->validated();
                $request = $this->requestService->createRequest($validated, Auth::user());
                
                $results[] = $request;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        // Return response with results and any errors
        $response = [
            'success' => count($errors) === 0,
            'total' => count($requestsData),
            'created' => count($results),
            'failed' => count($errors),
            'data' => $results,
        ];
        
        if (count($errors) > 0) {
            $response['errors'] = $errors;
        }
        
        $statusCode = count($results) > 0 ? 201 : 422;
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Get validation rules for a single request based on its category.
     */
    private function getValidationRulesForRequest(array $requestData): array
    {
        $categoryId = $requestData['category_id'] ?? 1;
        return \App\Services\CategoryHandlers\CategoryRequestHandlerFactory::getValidationRules($categoryId);
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
     *             @OA\Property(property="thread_id", type="integer", example=123, nullable=true, description="Chat thread ID for admin-user communication"),
     *             @OA\Property(property="total_price", type="number", format="float", example=150.00, description="Price calculated based on request area"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="area", type="object", description="Area information for the request",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *                 ),
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
     *             @OA\Property(property="scheduled_time", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Scheduled time for service"),
     *             @OA\Property(property="nurse_id", type="integer", example=1, nullable=true, description="ID of the assigned nurse (optional)")
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
        Log::info("RequestController::update called for request ID: {$id} by user: {$user->id} with role: {$user->role->name}");
        
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
            'discount_percentage' => 'sometimes|nullable|numeric|min:0|max:100',
            'nurse_id' => 'sometimes|nullable|integer|exists:nurses,id',
        ]);

        // Debug: Log the validated data
        Log::info("Validated data: " . json_encode($validated));

        // Handle discount update if provided
        if (array_key_exists('discount_percentage', $validated)) {
            $request = \App\Models\Request::findOrFail($id);
            
            // Calculate total price if not set
            if (!$request->total_price) {
                $this->calculateAndSetRequestPrice($request);
            }
            
            // Apply or remove discount
            $this->handleDiscountUpdate($request, $validated['discount_percentage']);
        }

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



    /**
     * Calculate and set the total price for a request.
     */
    private function calculateAndSetRequestPrice(\App\Models\Request $request): void
    {
        $serviceIds = $request->services->pluck('id')->toArray();
        
        $serviceAreaPrices = \App\Models\ServiceAreaPrice::whereIn('service_id', $serviceIds)
                                       ->where('area_id', $request->area_id)
                                       ->get();

        $totalPrice = 0;
        foreach ($serviceIds as $serviceId) {
            $price = $serviceAreaPrices->where('service_id', $serviceId)->first();
            if ($price) {
                $totalPrice += $price->price;
            }
        }

        $request->update([
            'total_price' => $totalPrice,
            'discounted_price' => $totalPrice
        ]);
    }

    /**
     * Handle discount percentage update for a request.
     */
    private function handleDiscountUpdate(\App\Models\Request $request, ?float $discountPercentage): void
    {
        if ($discountPercentage === null || $discountPercentage <= 0) {
            // Remove discount
            $request->update([
                'discount_percentage' => null,
                'discounted_price' => $request->total_price
            ]);
        } else {
            // Apply discount
            $discountAmount = ($request->total_price * $discountPercentage) / 100;
            $discountedPrice = $request->total_price - $discountAmount;

            $request->update([
                'discount_percentage' => $discountPercentage,
                'discounted_price' => max(0, $discountedPrice) // Ensure price doesn't go below 0
            ]);
        }
    }
}