<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import this for authorization checks

class UserController extends Controller
{
    use AuthorizesRequests; // Use this for policy-based authorization

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Get authenticated user details",
     *     description="Retrieve the authenticated user's profile information",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="location", type="string", example="New York"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *             @OA\Property(property="is_first_login", type="boolean", example=false),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * 
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user by ID (Admin only)",
     *     description="Retrieve a specific user's details by ID. Only accessible by admins.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="location", type="string", example="New York"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *             @OA\Property(property="is_first_login", type="boolean", example=false),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
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
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show(Request $request, $id = null)
    {
        // Check if an ID is provided, if not, fetch the authenticated user's details
        if ($id) {
            // Fetch the user by the provided ID
            $user = User::findOrFail($id);
    
            // Ensure only admins can view other users' details
            $this->authorize('view', $user);
        } else {
            // Fetch the authenticated user's details
            $user = $request->user();
            
            // If no authenticated user, return 401
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        }
    
        return response()->json($user, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update user details",
     *     description="Update user profile information. Users can only update their own profile, admins can update any user.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number"),
     *             @OA\Property(property="location", type="string", example="New York", description="User's location"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128, description="Latitude coordinate"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060, description="Longitude coordinate"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15", description="User's birth date (YYYY-MM-DD) - used for birthday notifications"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="User's area/region ID for region-based pricing"),
     *             @OA\Property(property="password", type="string", example="newpassword123", description="New password"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User updated successfully."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15", description="User's birth date"),
     *                 @OA\Property(property="area_id", type="integer", example=1, description="User's area/region ID"),
     *                 @OA\Property(property="location", type="string", example="New York"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *                 @OA\Property(property="is_first_login", type="boolean", example=false),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Cannot update this user"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Changing role is not allowed."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
    
        // Ensure 'role_id' is not allowed to be updated by the user
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|required|string|max:15|unique:users,phone_number,' . $user->id,
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'birth_date' => 'nullable|date|before:today',
            'area_id' => 'nullable|exists:areas,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
    
        // Check if role_id is present in the request and return an error if it is
        if ($request->has('role_id')) {
            return response()->json(['message' => 'Changing role is not allowed.'], 422);
        }
    
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }
    
        $user->update($validatedData);
    
        return response()->json(['message' => 'User updated successfully.', 'user' => $user], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/user/dashboard",
     *     summary="Get user dashboard",
     *     description="Retrieve user dashboard with active requests count, recent services, most requested services (manually selected by admin), suggested doctors (manually selected by admin), and trusted images (managed by admin). All data is automatically included.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="active_requests", type="integer", example=3, description="Number of pending requests"),
     *             @OA\Property(property="recent_services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="problem_description", type="string", example="Need nursing care"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="services", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Home Nursing"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00)
     *                 )),
     *                 @OA\Property(property="area", type="object", nullable=true)
     *             ), description="Recent 5 service requests"),
     *             @OA\Property(property="most_requested_services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/services/..."),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="category_id", type="integer", example=1)
     *             ), description="Most requested services manually selected by admin"),
     *             @OA\Property(property="suggested_doctors", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/doctors/..."),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="specification", type="string", example="Cardiologist"),
     *                 @OA\Property(property="job_name", type="string", example="Senior Cardiologist"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="years_of_experience", type="integer", example=15),
     *                 @OA\Property(property="category_id", type="integer", example=1)
     *             ), description="Suggested doctors manually selected by admin from Category 8"),
     *             @OA\Property(property="trusted_images", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/trusted-images/...")
     *             ), description="Trusted images managed by admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User role required"
     *     )
     * )
     */
    public function dashboard()
    {
        $user = Auth::user();
        $locale = app()->getLocale() ?: 'en';

        // Get all user requests with relationships in a single query
        $requests = $user->requests()
            ->with(['services', 'area'])
            ->latest('created_at')
            ->get();

        // Count active requests from the same dataset
        $activeRequests = $requests
            ->whereIn('status', ['submitted', 'assigned', 'in_progress'])
            ->count();

        // Get recent 5 requests and format them
        $recentServices = $requests
            ->take(5)
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'full_name' => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'problem_description' => $request->problem_description,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'services' => $request->services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'price' => $service->pivot->price ?? null, // If price is stored in pivot table
                        ];
                    }),
                    'area' => $request->area ? [
                        'id' => $request->area->id,
                        'name' => $request->area->name,
                    ] : null,
                ];
            });

        // Get Most Requested Services
        $mostRequestedServices = \App\Models\MostRequestedService::with(['service.areaPrices.area', 'service.category'])
            ->orderBy('order')
            ->get()
            ->map(function ($item) use ($locale) {
                $service = $item->service;
                $translation = $service ? $service->translate($locale) : null;
                return [
                    'id' => $service->id,
                    'name' => $translation?->name ?? $service->name,
                    'image' => $service->image_url,
                    'price' => $service->price,
                    'description' => $translation?->description ?? $service->description,
                    'category_id' => $service->category_id,
                ];
            });

        // Get Suggested Doctors
        $suggestedDoctors = \App\Models\SuggestedDoctor::with(['doctor.doctorCategory', 'doctor.areaPrices.area'])
            ->orderBy('order')
            ->get()
            ->map(function ($item) use ($locale) {
                $doctor = $item->doctor;
                $translation = $doctor ? $doctor->translate($locale) : null;
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'image' => $doctor->image_url,
                    'price' => $doctor->price,
                    'specification' => $translation?->specification ?? $doctor->specification,
                    'job_name' => $translation?->job_name ?? $doctor->job_name,
                    'description' => $translation?->description ?? $doctor->description,
                    'years_of_experience' => $doctor->years_of_experience,
                    'category_id' => $doctor->doctor_category_id,
                ];
            });

        // Get Trusted Images
        $trustedImages = \App\Models\TrustedImage::orderBy('order')
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image' => $image->image_url,
                ];
            });

        return response()->json([
            'active_requests' => $activeRequests,
            'recent_services' => $recentServices,
            'most_requested_services' => $mostRequestedServices,
            'suggested_doctors' => $suggestedDoctors,
            'trusted_images' => $trustedImages,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/submit-location",
     *     summary="Submit user location on first login",
     *     description="Submit user's location coordinates on first login. Can only be called once per user.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude","longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128, description="Latitude coordinate (-90 to 90)"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060, description="Longitude coordinate (-180 to 180)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location saved successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Location already set",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location already set.")
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
    public function submitLocationOnFirstLogin(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
    
        $user = Auth::user();
    
        // Check if user has already set their location
        if (!$user->is_first_login) {
            return response()->json(['message' => 'Location already set.'], 400);
        }
    
        // Ensure the user is updated correctly
        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_first_login' => false, // Update this field to false
        ]);
    
        return response()->json(['message' => 'Location saved successfully.'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Get admin dashboard",
     *     description="Retrieve admin dashboard with system statistics",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Admin dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_users", type="integer", example=150, description="Total number of users"),
     *             @OA\Property(property="total_requests", type="integer", example=75, description="Total number of requests"),
     *             @OA\Property(property="pending_requests", type="integer", example=12, description="Number of pending requests"),
     *             @OA\Property(property="total_nurses", type="integer", example=25, description="Total number of nurses")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     )
     * )
     */
    public function adminDashboard()
    {
        $this->authorize('viewAny', User::class);

        $totalUsers = User::count();
        $totalRequests = \App\Models\Request::count();
        $pendingRequests = \App\Models\Request::where('status', 'pending')->count();
        $totalNurses = \App\Models\Nurse::count();

        return response()->json([
            'total_users' => $totalUsers,
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'total_nurses' => $totalNurses,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="List all users (Admin only)",
     *     description="Retrieve a list of all users in the system. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users list retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="location", type="string", example="New York"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *                 @OA\Property(property="is_first_login", type="boolean", example=false),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     )
     * )
     */
    public function index()
    {
        // Ensure only admins can list users
        $this->authorize('viewAny', User::class);

        $users = User::all();
        return response()->json($users, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Create a new user (Admin only)",
     *     description="Create a new user account. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone_number","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Jane Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com", description="User's email address"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number"),
     *             @OA\Property(property="location", type="string", example="Los Angeles", description="User's location"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15", description="User's birth date (YYYY-MM-DD) - optional, used for birthday notifications"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="User's area/region ID (optional)"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123", description="User's password"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123", description="Password confirmation"),
     *             @OA\Property(property="role_id", type="integer", example=2, description="User role ID (optional, defaults to user)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="jane@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15", description="User's birth date"),
     *                 @OA\Property(property="area_id", type="integer", example=1, description="User's area/region ID"),
     *                 @OA\Property(property="location", type="string", example="Los Angeles"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
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
        // Ensure only admins can create a new user
        $this->authorize('create', User::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:15|unique:users',
            'location' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'area_id' => 'nullable|exists:areas,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'location' => $validatedData['location'] ?? null,
            'birth_date' => $validatedData['birth_date'] ?? null,
            'area_id' => $validatedData['area_id'] ?? null,
            'password' => Hash::make($validatedData['password']),
            'role_id' => $request->role_id ?? 2, // Admin can specify role, default to 'user'
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="Delete a user (Admin only)",
     *     description="Delete a user account from the system. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully.")
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
     *         description="User not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }


}