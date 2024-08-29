<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Request as UserRequest;
use App\Models\Service;
use App\Models\Category;

class AdminController extends Controller
{
    /**
     * Admin dashboard (example placeholder).
     */
    public function dashboard()
    {
        // Return a summary of admin-specific information, such as total requests
        $totalRequests = UserRequest::count();
        $pendingRequests = UserRequest::where('status', 'pending')->count();

        return response()->json([
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
        ], 200);
    }

    /**
     * Add a new nurse.
     */
    public function addNurse(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:nurses',
            'address' => 'required|string|max:255',
            'profile_picture' => 'nullable|string', // Assume base64 encoded or URL
        ]);

        $nurse = new Nurse();
        $nurse->name = $request->name;
        $nurse->phone_number = $request->phone_number;
        $nurse->address = $request->address;
        $nurse->profile_picture = $request->profile_picture;
        $nurse->save();

        return response()->json(['message' => 'Nurse added successfully.'], 201);
    }

    /**
     * Update nurse details.
     */
    public function updateNurse(Request $request, $id)
    {
        $nurse = Nurse::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:15|unique:nurses,phone_number,' . $nurse->id,
            'address' => 'sometimes|required|string|max:255',
            'profile_picture' => 'nullable|string',
        ]);

        $nurse->update($request->only(['name', 'phone_number', 'address', 'profile_picture']));

        return response()->json(['message' => 'Nurse updated successfully.'], 200);
    }

    /**
     * Delete a nurse.
     */
    public function deleteNurse($id)
    {
        $nurse = Nurse::findOrFail($id);
        $nurse->delete();

        return response()->json(['message' => 'Nurse deleted successfully.'], 200);
    }

    /**
     * View all user requests.
     */
    public function viewRequests()
    {
        $requests = UserRequest::with(['user', 'service'])
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json(['requests' => $requests], 200);
    }

    /**
     * Manage services - view all services.
     */
    public function viewServices()
    {
        $services = Service::with('category')->get();

        return response()->json(['services' => $services], 200);
    }

    /**
     * Add or update service.
     */
    public function addOrUpdateService(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'category_id' => 'required|exists:categories,id',
        ]);

        $service = Service::updateOrCreate(
            ['id' => $request->id], // Update if exists
            $request->only(['name', 'description', 'price', 'discount_price', 'category_id'])
        );

        return response()->json(['message' => 'Service saved successfully.', 'service' => $service], 201);
    }
}