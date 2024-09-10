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
        $validatedData = $request->validate([
            'nurse_id' => 'required|exists:nurses,id',
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
            'scheduled_time' => 'nullable|date',
            'location' => 'required|string',
            'time_type' => 'required|in:full-time,part-time', // New field for time type
        ]);
    
        try {
            $user = Auth::user();
    
            // Check if the user's location is set (latitude and longitude)
            if (is_null($user->latitude) || is_null($user->longitude)) {
                return response()->json([
                    'message' => 'Please set your location on the map before creating a request.',
                ], 422); // Use 422 Unprocessable Entity for validation errors
            }
    
            // Create a new nurse request
            $nurseRequest = new NurseRequest();
            $nurseRequest->user_id = $user->id;
            $nurseRequest->nurse_id = $validatedData['nurse_id'];
            $nurseRequest->status = 'pending';
            $nurseRequest->scheduled_time = $validatedData['scheduled_time'];
            $nurseRequest->location = $validatedData['location'];
            $nurseRequest->time_type = $validatedData['time_type']; // Save the time type
            $nurseRequest->save();
    
            // Attach multiple services to the request
            $nurseRequest->services()->attach($validatedData['service_ids']);
    
            $latitude = $user->latitude;
            $longitude = $user->longitude;
    
            event(new UserRequestedService($nurseRequest));
    
            return response()->json([
                'message' => 'Request created successfully.',
                'request' => $nurseRequest->load('services'),
                'user_location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
            ], 201);
        } catch (\Exception $e) {
            // Log the exception and return a generic error message
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
    
        // Return the request along with its related data
        return response()->json($nurseRequest, 200);
    }
    

    /**
     * Update the specified request in storage (Admin only).
     */
    public function update(Request $request, $id)
    {
        $nurseRequest = NurseRequest::findOrFail($id);
        $this->authorize('update', $nurseRequest); // Ensure only Admin can update

        $validatedData = $request->validate([
            'status' => 'required|string|in:pending,approved,completed,canceled', // Status update
            'scheduled_time' => 'nullable|date', // Optional scheduling
            'location' => 'sometimes|string', // Optional location update
            'nurse_id' => 'sometimes|exists:nurses,id', // Optional nurse change
            'service_id' => 'sometimes|exists:services,id', // Optional service change
            'time_type' => 'sometimes|in:full-time,part-time', // Include time_type in update validation

        ]);

        $nurseRequest->update($validatedData);

        // Dispatch the event
        event(new AdminUpdatedRequest($nurseRequest));

        return response()->json(['message' => 'Request updated successfully.', 'request' => $nurseRequest], 200);
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