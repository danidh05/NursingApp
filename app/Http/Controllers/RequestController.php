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
use Illuminate\Validation\ValidationException;

class RequestController extends Controller
{
    use AuthorizesRequests; // Use this for policy-based authorization

    /**
     * Display a listing of the requests (Admin only).
     */
    public function index()
    {
        $user = Auth::user();
    
        // For admin, fetch all requests; for users, fetch only their own requests
        if ($user->role_id === 1) { // Assuming 1 is the admin role
            $requests = NurseRequest::with(['user', 'nurse', 'services'])->get();
        } else {
            $requests = NurseRequest::with(['nurse', 'services'])
                ->where('user_id', $user->id)
                ->get();
        }
    
        return response()->json(['requests' => $requests], 200);
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
                'time_type' => 'required|in:full-time,part-time',
                'problem_description' => 'nullable|string|max:1000',
                'nurse_gender' => 'required|in:male,female',
                'full_name' => 'required|string|max:255', // Add validation for full_name
                'phone_number' => 'required|string|max:15', // Add validation for phone_number
            ]);
    
            // Create a new request including the new fields
            $nurseRequest = NurseRequest::create([
                'user_id' => $user->id,
                'nurse_id' => $validatedData['nurse_id'] ?? null,  // Set to null if not provided
                'status' => 'pending',
                'scheduled_time' => $validatedData['scheduled_time'] ?? now(),  // Set to null if not provided
                'ending_time' => $validatedData['ending_time'] ?? null,  // Set to null if not provided
                'location' => $validatedData['location'],
                'time_type' => $validatedData['time_type'],
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
            return response()->json(['error' => 'Server Error'], 500);
        }
    }
    
    

    /**
     * Display the specified request along with user's exact location and nurse details (Available to both Admin and User).
     */
    public function show($id)
    {
        // Fetch the request with all related details using eager loading
        $nurseRequest = NurseRequest::with(['user', 'nurse', 'services'])->findOrFail($id);
        
        // Ensure the user is authorized to view this request
        $this->authorize('view', $nurseRequest);
        
        // Calculate time_needed_to_arrive if scheduled_time is set
        $timeNeededToArrive = null;
        if ($nurseRequest->status !== 'pending' && $nurseRequest->scheduled_time) {
            $timeNeededToArrive = round(now()->diffInMinutes($nurseRequest->scheduled_time));
        }
    
        // Return the request along with its related data and calculated time_needed_to_arrive
        return response()->json(array_merge($nurseRequest->toArray(), [
            'time_needed_to_arrive' => $timeNeededToArrive,
        ]), 200);
    }
    

    /**
     * Update the specified request in storage (Admin only).
     */
    public function update(Request $request, $id)
    {
        $nurseRequest = NurseRequest::findOrFail($id);
        $this->authorize('update', $nurseRequest); // Ensure only Admin can update
    
        // Validate the incoming request data
        $validatedData = $request->validate([
            'status' => 'required|string|in:pending,approved,completed,canceled',
            'scheduled_time' => 'nullable|date', // Scheduled time can be null for immediate requests
            'ending_time' => 'nullable|date|after:scheduled_time',
            'location' => 'sometimes|string',
            'nurse_id' => 'sometimes|exists:nurses,id',
            'service_ids' => 'sometimes|array',
            'service_ids.*' => 'exists:services,id',
            'time_type' => 'sometimes|in:full-time,part-time',
            'problem_description' => 'nullable|string|max:1000',
            'nurse_gender' => 'sometimes|in:male,female',
            'time_needed_to_arrive' => 'nullable|integer|min:1', // This will hold the minutes needed for arrival
        ]);

        $timeNeededToArrive = $validatedData['time_needed_to_arrive'] ?? null;
    
        // Handle scheduled request updates, or immediate requests that already have now() as scheduled time
        if ($nurseRequest->scheduled_time && isset($validatedData['time_needed_to_arrive'])) {
            // If it's a scheduled request, calculate the new scheduled time
            $nurseRequest->scheduled_time = now()->addMinutes($validatedData['time_needed_to_arrive']);
        }
    
        // Update other request details
        $nurseRequest->update($validatedData);
    
        // Sync the services if provided
        if (isset($validatedData['service_ids'])) {
            $nurseRequest->services()->sync($validatedData['service_ids']);
        }
    
        // Dispatch the event for request update
        event(new AdminUpdatedRequest($nurseRequest));
    
        return response()->json([
            'message' => 'Request updated successfully.',
            'request' => $nurseRequest,
            'time_needed_to_arrive' => $validatedData['time_needed_to_arrive'] ?? null, // Send this in the response
        ], 200);
    }
    
    

    /**
     * Remove the specified request from storage (Admin only).
     */
    public function destroy($id)
    {
        $nurseRequest = NurseRequest::findOrFail($id);
        $this->authorize('delete', $nurseRequest); // Ensure only Admin can delete

        $nurseRequest->delete();

        return response()->json(['message' => 'Request deleted successfully.'], 200);
    }
}