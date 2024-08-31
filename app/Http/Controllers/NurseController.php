<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nurse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class NurseController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the nurses (Available to both Admin and User).
     */
    public function index()
    {
        $nurses = Nurse::all();
        return response()->json(['nurses' => $nurses], 200);
    }

    /**
     * Store a newly created nurse in storage (Admin only).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Nurse::class); // Ensure only Admin can create

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:nurses',
            'address' => 'required|string|max:255',
            'profile_picture' => 'nullable|string|url', // Validate as URL if applicable
        ]);

        $nurse = Nurse::create($validatedData);
        return response()->json(['message' => 'Nurse added successfully.', 'nurse' => $nurse], 201);
    }

    /**
     * Display the specified nurse (Available to both Admin and User).
     */
    public function show($id)
    {
        $nurse = Nurse::findOrFail($id);
        return response()->json(['nurse' => $nurse], 200);
    }

    /**
     * Update the specified nurse in storage (Admin only).
     */
    public function update(Request $request, $id)
    {
        $nurse = Nurse::findOrFail($id);
        $this->authorize('update', $nurse); // Ensure only Admin can update

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:15|unique:nurses,phone_number,' . $nurse->id,
            'address' => 'sometimes|string|max:255',
            'profile_picture' => 'nullable|string|url', // Validate as URL if applicable
        ]);

        $nurse->update($validatedData);
        return response()->json(['message' => 'Nurse updated successfully.', 'nurse' => $nurse], 200);
    }

    /**
     * Remove the specified nurse from storage (Admin only).
     */
    public function destroy($id)
    {
        $nurse = Nurse::findOrFail($id);
        $this->authorize('delete', $nurse); // Ensure only Admin can delete

        $nurse->delete();

        return response()->json(['message' => 'Nurse deleted successfully.'], 200);
    }
}