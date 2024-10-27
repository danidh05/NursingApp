<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Request as NurseRequest; // Correct the alias for consistency
use App\Models\User;
use App\Models\Nurse;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import for policy-based authorization
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // Add this import at the to
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class RequestController extends Controller
{
    use AuthorizesRequests; // Use this for policy-based authorization

    /**
     * Display a listing of the requests (Admin only).
     */
    public function index()
    {
        $user = Auth::user();
        
        // Admin (role_id 1): Show only non-deleted requests
        if ($user->role_id === 1) {
            $requests = NurseRequest::whereNull('deleted_at') // Exclude soft-deleted requests explicitly
                                    ->with(['user', 'nurse', 'services'])
                                    ->get();
        } else {
            // User (role_id 2): Show own requests, including soft-deleted ones
            $requests = NurseRequest::withTrashed() // Include soft-deleted
                                    ->with(['nurse', 'services'])
                                    ->where('user_id', $user->id)
                                    ->get();
        }
    
        return response()->json(['requests' => $requests], 200);
    }
    
    
    /**
     * Display the specified request along with user's exact location and nurse details (Available to both Admin and User).
     */
public function show($id)
{
    $nurseRequest = NurseRequest::withTrashed()->with(['user', 'nurse', 'services'])->findOrFail($id);
    $this->authorize('view', $nurseRequest);

    // Admin cannot view soft-deleted requests
    if ($nurseRequest->trashed() && Auth::user()->role_id === 1) {
        abort(404);
    }

    $cacheKey = 'time_needed_to_arrive_' . $nurseRequest->id;
    $cachedData = Cache::get($cacheKey);

    // Calculate remaining time if cache exists
    if ($cachedData) {
        $timeNeededToArrive = $cachedData['time_needed'];
        $startTime = $cachedData['start_time'];

        // Calculate the elapsed time in minutes since the cache was created
        $elapsedTime = now()->diffInMinutes($startTime);

        // Calculate the remaining time (initial time - elapsed time)
        $remainingTime = max($timeNeededToArrive + $elapsedTime, 0); // Ensure it's not negative
    } else {
        $remainingTime = null; // If not in cache, return null or default value
    }

    return response()->json(array_merge($nurseRequest->toArray(), [
        'time_needed_to_arrive' => $remainingTime,
    ]), 200);
}


    

    /**
     * Store a newly created request in storage (User only).
     */


public function store(Request $request)
{
    try {
        $user = Auth::user();

        // Ensure user's latitude and longitude are set before validation
        if (is_null($user->latitude) || is_null($user->longitude)) {
            return response()->json([
                'message' => 'Please set your location on the map before creating a request.',
            ], 422);
        }

        // Validate the incoming request data, including the new fields
        $validatedData = $request->validate([
            'nurse_id' => 'nullable|exists:nurses,id',
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
            'scheduled_time' => 'nullable|date',
            'ending_time' => 'nullable|date|after:scheduled_time',
            'location' => 'required|string',
            'time_type' => 'nullable|in:full-time,part-time', // Make time_type nullable
            'problem_description' => 'nullable|string|max:1000',
            'nurse_gender' => 'required|in:male,female',
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
        ]);

        // Convert scheduled_time and ending_time to 'Asia/Beirut' timezone
        $scheduledTime = isset($validatedData['scheduled_time']) 
            ? Carbon::parse($validatedData['scheduled_time'])->setTimezone('Asia/Beirut')
            : Carbon::now('Asia/Beirut');

        $endingTime = isset($validatedData['ending_time']) 
            ? Carbon::parse($validatedData['ending_time'])->setTimezone('Asia/Beirut')
            : null;

        // Create a new request including the new fields
        $nurseRequest = NurseRequest::create([
            'user_id' => $user->id,
            'nurse_id' => $validatedData['nurse_id'] ?? null,  // Set to null if not provided
            'status' => 'pending',
            'scheduled_time' => $scheduledTime,
            'ending_time' => $endingTime,  // Set to null if not provided
            'location' => $validatedData['location'],
            'time_type' => $validatedData['time_type'] ?? null,
            'problem_description' => $validatedData['problem_description'] ?? null,  // Set to null if not provided
            'nurse_gender' => $validatedData['nurse_gender'],
            'full_name' => $validatedData['full_name'],
            'phone_number' => $validatedData['phone_number'],
        ]);

        // Attach the services to the request
        $nurseRequest->services()->attach($validatedData['service_ids']);
        \Log::info('Nurse request created with ID: ' . $nurseRequest->id);

        // Dispatch the event for the new request
        event(new UserRequestedService($nurseRequest));
        \Log::info('UserRequestedService event dispatched for request ID: ' . $nurseRequest->id);

        return response()->json([
            'message' => 'Request created successfully.',
            'request' => $nurseRequest->load('services'),
            'user_location' => [
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ],
        ], 201);
    } catch (ValidationException $e) {
        // Handle validation errors
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Log and return a generic error message
        \Log::error('Error creating request: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    
    

    

    /**
     * Update the specified request in storage (Admin only).
     */
public function update(Request $request, $id)
{
    $nurseRequest = NurseRequest::findOrFail($id);
    $this->authorize('update', $nurseRequest);

    $validatedData = $request->validate([
        'status' => 'required|string|in:pending,approved,completed,canceled',
        'scheduled_time' => 'nullable|date',
        'ending_time' => 'nullable|date|after:scheduled_time',
        'location' => 'sometimes|string',
        'nurse_id' => 'sometimes|exists:nurses,id',
        'service_ids' => 'sometimes|array',
        'service_ids.*' => 'exists:services,id',
        'time_type' => 'nullable|in:full-time,part-time',
        'problem_description' => 'nullable|string|max:1000',
        'nurse_gender' => 'sometimes|in:male,female',
        'time_needed_to_arrive' => 'nullable|integer|min:1', // Not stored in DB
    ]);

    $timeNeededToArrive = $validatedData['time_needed_to_arrive'] ?? null;

    // Cache the time_needed_to_arrive for 1 hour if provided
    if ($timeNeededToArrive !== null) {
        $cacheKey = 'time_needed_to_arrive_' . $nurseRequest->id;
        $cacheValue = [
            'time_needed' => $timeNeededToArrive,  // Initial time in minutes
            'start_time' => now(),  // Store the time when the cache was created
        ];
        Cache::put($cacheKey, $cacheValue, 3600); // Cache for 1 hour (3600 seconds)
    }

    // Update the request excluding time_needed_to_arrive
    $nurseRequest->update(array_filter($validatedData, fn($key) => $key !== 'time_needed_to_arrive', ARRAY_FILTER_USE_KEY));

    // Sync services if provided
    if (isset($validatedData['service_ids'])) {
        $nurseRequest->services()->sync($validatedData['service_ids']);
    }

    // Dispatch event for request update
    event(new AdminUpdatedRequest($nurseRequest));

    return response()->json([
        'message' => 'Request updated successfully.',
        'request' => $nurseRequest,
        'time_needed_to_arrive' => $timeNeededToArrive,
    ], 200);
}

    
    
    

    /**
     * Remove the specified request from storage (Admin only).
     */
    public function destroy($id)
    {
        $nurseRequest = NurseRequest::findOrFail($id);
        $this->authorize('delete', $nurseRequest); // Ensure only Admin can delete
    
        // Perform a soft delete instead of a hard delete
        $nurseRequest->delete();
    
        return response()->json(['message' => 'Request removed from admin view, but still available to users.'], 200);
    }
    
}