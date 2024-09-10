<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nurse;
use App\Models\Rating;
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
        $this->authorize('create', Nurse::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:nurses',
            'address' => 'required|string|max:255',
            'profile_picture' => 'nullable|string|url',
            'gender' => 'required|in:male,female',
        ]);

        $nurse = Nurse::create($validatedData);
        return response()->json(['message' => 'Nurse added successfully.', 'nurse' => $nurse], 201);
    }

    /**
     * Display the specified nurse (Available to both Admin and User).
     */
    public function show($id)
    {
        $nurse = Nurse::with('ratings.user')->findOrFail($id);

        return response()->json([
            'nurse' => $nurse,
            'average_rating' => $nurse->averageRating(),
        ], 200);
    }

    /**
     * Update the specified nurse in storage (Admin only).
     */
    public function update(Request $request, $id)
    {
        $nurse = Nurse::findOrFail($id);
        $this->authorize('update', $nurse);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:15|unique:nurses,phone_number,' . $nurse->id,
            'address' => 'sometimes|string|max:255',
            'profile_picture' => 'nullable|string|url',
            'gender' => 'sometimes|required|in:male,female',
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
        $this->authorize('delete', $nurse);

        $nurse->delete();

        return response()->json(['message' => 'Nurse deleted successfully.'], 200);
    }

    /**
     * Store a new rating for a nurse.
     */
    public function rate(Request $request, $nurseId)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $nurse = Nurse::findOrFail($nurseId);

        // Check if the user has already rated this nurse
        $existingRating = Rating::where('nurse_id', $nurseId)->where('user_id', Auth::id())->first();
        if ($existingRating) {
            return response()->json(['message' => 'You have already rated this nurse.'], 422); // Change to 422 for validation error
        }

        $rating = new Rating([
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $nurse->ratings()->save($rating);

        return response()->json(['message' => 'Rating submitted successfully.', 'rating' => $rating], 201);
    }
}