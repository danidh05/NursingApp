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
            $requests = NurseRequest::with(['user', 'nurse', 'service'])->get();
        } else {
            $requests = NurseRequest::with(['nurse', 'service'])
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
        // Validate input
        $validatedData = $request->validate([
            'nurse_id' => 'required|exists:nurses,id',
            'service_id' => 'required|exists:services,id',
            'scheduled_time' => 'nullable|date',
            'location' => 'required|string',
        ]);
    
        try {
            // Business logic to create the request
            $user = Auth::user();
    
            $nurseRequest = new NurseRequest(); // Correct alias
            $nurseRequest->user_id = $user->id;
            $nurseRequest->nurse_id = $validatedData['nurse_id'];
            $nurseRequest->service_id = $validatedData['service_id'];
            $nurseRequest->status = 'pending';
            $nurseRequest->scheduled_time = $validatedData['scheduled_time'];
            $nurseRequest->location = $validatedData['location'];
            $nurseRequest->save();
    
            // Retrieve latitude and longitude from the user for response
            $latitude = $user->latitude;
            $longitude = $user->longitude;
    
            // Dispatch the event
            event(new UserRequestedService($nurseRequest));
    
            return response()->json([
                'message' => 'Request created successfully.',
                'request' => $nurseRequest,
                'user_location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in store method: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }
    

    /**
     * Display the specified request along with user's exact location and nurse details (Available to both Admin and User).
     */
    public function show($id)
    {
        // Fetch the request with all related details using eager loading
        $nurseRequest = NurseRequest::with(['user', 'nurse', 'service'])->findOrFail($id);
        
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