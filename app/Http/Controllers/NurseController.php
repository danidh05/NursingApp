<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nurse;
use Illuminate\Support\Facades\Auth;

class NurseController extends Controller
{
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
            'profile_picture' => 'nullable|string', // Can be a URL or base64 string
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
        $this->authorize('update', Nurse::class); // Ensure only Admin can update

        $nurse = Nurse::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:15|unique:nurses,phone_number,' . $nurse->id,
            'address' => 'sometimes|string|max:255',
            'profile_picture' => 'nullable|string',
        ]);

        $nurse->update($validatedData);
        return response()->json(['message' => 'Nurse updated successfully.', 'nurse' => $nurse], 200);
    }

    /**
     * Remove the specified nurse from storage (Admin only).
     */
    public function destroy($id)
    {
        $this->authorize('delete', Nurse::class); // Ensure only Admin can delete

        $nurse = Nurse::findOrFail($id);
        $nurse->delete();

        return response()->json(['message' => 'Nurse deleted successfully.'], 200);
    }
}