<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import AuthorizesRequests

class ServiceController extends Controller
{
    use AuthorizesRequests; // Use the AuthorizesRequests trait
    /**
     * Display a listing of the services (Accessible by both Admin and User).
     */
    public function index()
    {
        $services = Service::all();
        return response()->json(['services' => $services], 200);
    }

    /**
     * Store a newly created service in storage (Admin only).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Service::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'category_id' => 'required|exists:categories,id', // Assuming there's a category table
        ]);

        $service = Service::create($validatedData);

        return response()->json(['message' => 'Service created successfully.', 'service' => $service], 201);
    }

    /**
     * Display the specified service (Accessible by both Admin and User).
     */
    public function show($id)
    {
        $service = Service::findOrFail($id);
        return response()->json(['service' => $service], 200);
    }

    /**
     * Update the specified service in storage (Admin only).
     */
    public function update(Request $request, Service $service)
    {
        // Ensure the correct policy is called with the proper arguments
        $this->authorize('update', $service);
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id', // Include category_id validation if needed
        ]);
    
        $service->update($validatedData);
    
        return response()->json(['message' => 'Service updated successfully.'], 200);
    }
    
    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);
    
        // Perform a hard delete
        $service->delete();
    
        return response()->json(['message' => 'Service deleted successfully.'], 200);
    }
    

    
}