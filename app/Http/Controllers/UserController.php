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
     * List all users (Admin only).
     */
    public function index()
    {
        // Ensure only admins can list users
        $this->authorize('viewAny', User::class);

        $users = User::all();
        return response()->json($users, 200);
    }

    /**
     * Create a new user (Admin only).
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
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'location' => $validatedData['location'] ?? null,
            'password' => Hash::make($validatedData['password']),
            'role_id' => $request->role_id ?? 2, // Admin can specify role, default to 'user'
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
     * Display a user's details (Admin or the user themselves).
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('view', $user);

        return response()->json($user, 200);
    }

    /**
     * Update a user's details (Admin or the user themselves).
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
     * Delete a user (Admin only).
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }

    /**
     * Update the user's location on first login.
     */
    public function submitLocationOnFirstLogin(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
    
        $user = Auth::user();
    
        if ($user->is_first_login) {
            // Ensure the user is updated correctly
            $user->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_first_login' => false, // Update this field to false
            ]);
    
            return response()->json(['message' => 'Location saved successfully.'], 200);
        }
    
        return response()->json(['message' => 'Location already set.'], 400);
    }
    
    

    /**
     * User dashboard (for "user" role).
     */
    public function dashboard()
    {
        $user = Auth::user();

        $activeRequests = $user->requests()->where('status', 'pending')->count();

        $recentServices = $user->requests()
            ->with('service')
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json([
            'active_requests' => $activeRequests,
            'recent_services' => $recentServices,
        ], 200);
    }
}