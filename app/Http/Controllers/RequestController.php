<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Request as UserRequest;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import for policy-based authorization

class RequestController extends Controller
{
    use AuthorizesRequests; // Use this for policy-based authorization

    /**
     * Display a listing of the requests (Admin only).
     */
    public function index()
    {
        // Ensure only admins can view all requests
        $this->authorize('viewAny', UserRequest::class);

        $requests = UserRequest::with(['user', 'nurse', 'service'])->get(); // Eager load related data
        return response()->json(['requests' => $requests], 200);
    }

    /**
     * Store a newly created request in storage (User only).
     */
    public function store(Request $request)
    {
        // Validate input including nurse_id and service_id
        $validatedData = $request->validate([
            'nurse_id' => 'required|exists:nurses,id', // Nurse being requested
            'service_id' => 'required|exists:services,id', // Service linked to the request
            'scheduled_time' => 'nullable|date', // Nullable if scheduling isn't needed
            'location' => 'required|string', // User-provided location description
        ]);
    
        $user = Auth::user();
    
        // Retrieve latitude and longitude from the user's profile and cast as floats
        $latitude = (float) $user->latitude;
        $longitude = (float) $user->longitude;
    
        // Create a new request with the user's location details
        $nurseRequest = new UserRequest();
        $nurseRequest->user_id = $user->id;
        $nurseRequest->nurse_id = $validatedData['nurse_id'];
        $nurseRequest->service_id = $validatedData['service_id'];
        $nurseRequest->status = 'pending'; // Default status
        $nurseRequest->scheduled_time = $validatedData['scheduled_time'];
        $nurseRequest->location = $validatedData['location'];
        $nurseRequest->save();
    
        return response()->json([
            'message' => 'Request created successfully.',
            'request' => $nurseRequest,
            'user_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ], 201);
    }
    

    /**
     * Display the specified request along with user's exact location and nurse details (Available to both Admin and User).
     */
    public function show($id)
    {
        $nurseRequest = UserRequest::with(['user', 'nurse', 'service'])->findOrFail($id);
        $this->authorize('view', $nurseRequest); // Ensure proper authorization

        // Get the user who made the request
        $user = $nurseRequest->user;

        return response()->json([
            'request' => $nurseRequest,
            'user_location' => [
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ],
        ], 200);
    }

    /**
     * Update the specified request in storage (Admin only).
     */
    public function update(Request $request, $id)
    {
        $nurseRequest = UserRequest::findOrFail($id);
        $this->authorize('update', $nurseRequest); // Ensure only Admin can update

        $validatedData = $request->validate([
            'status' => 'required|string|in:pending,approved,completed,canceled', // Status update
            'scheduled_time' => 'nullable|date', // Optional scheduling
            'location' => 'sometimes|string', // Optional location update
            'nurse_id' => 'sometimes|exists:nurses,id', // Optional nurse change
            'service_id' => 'sometimes|exists:services,id', // Optional service change
        ]);

        $nurseRequest->update($validatedData);

        return response()->json(['message' => 'Request updated successfully.', 'request' => $nurseRequest], 200);
    }

    /**
     * Remove the specified request from storage (Admin only).
     */
    public function destroy($id)
    {
        $nurseRequest = UserRequest::findOrFail($id);
        $this->authorize('delete', $nurseRequest); // Ensure only Admin can delete

        $nurseRequest->delete();

        return response()->json(['message' => 'Request deleted successfully.'], 200);
    }
}