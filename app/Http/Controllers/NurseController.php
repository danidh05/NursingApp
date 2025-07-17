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
     * @OA\Get(
     *     path="/api/nurses",
     *     summary="List all nurses",
     *     description="Retrieve a list of all nurses. Available to both users and admins.",
     *     tags={"Nurses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nurses list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="nurses", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sarah Johnson"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="address", type="string", example="123 Medical Center Dr"),
     *                 @OA\Property(property="profile_picture", type="string", example="https://example.com/photo.jpg"),
     *                 @OA\Property(property="gender", type="string", example="female", enum={"male","female"}),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $nurses = Nurse::all();
        return response()->json(['nurses' => $nurses], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/nurses",
     *     summary="Create a new nurse (Admin only)",
     *     description="Create a new nurse account. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","phone_number","address","gender"},
     *             @OA\Property(property="name", type="string", example="Sarah Johnson", description="Nurse's full name"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="Nurse's phone number"),
     *             @OA\Property(property="address", type="string", example="123 Medical Center Dr", description="Nurse's address"),
     *             @OA\Property(property="profile_picture", type="string", example="https://example.com/photo.jpg", description="URL to profile picture"),
     *             @OA\Property(property="gender", type="string", example="female", enum={"male","female"}, description="Nurse's gender")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nurse created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nurse added successfully."),
     *             @OA\Property(property="nurse", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sarah Johnson"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="address", type="string", example="123 Medical Center Dr"),
     *                 @OA\Property(property="profile_picture", type="string", example="https://example.com/photo.jpg"),
     *                 @OA\Property(property="gender", type="string", example="female"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/nurses/{id}",
     *     summary="Get nurse details",
     *     description="Retrieve details of a specific nurse including ratings. Available to both users and admins.",
     *     tags={"Nurses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Nurse ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nurse details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="nurse", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sarah Johnson"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="address", type="string", example="123 Medical Center Dr"),
     *                 @OA\Property(property="profile_picture", type="string", example="https://example.com/photo.jpg"),
     *                 @OA\Property(property="gender", type="string", example="female"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="ratings", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="comment", type="string", example="Excellent care provided"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     )
     *                 ))
     *             ),
     *             @OA\Property(property="average_rating", type="number", format="float", example=4.5, description="Average rating out of 5")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nurse not found"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/nurses/{id}",
     *     summary="Update nurse details (Admin only)",
     *     description="Update a nurse's information. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Nurse ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Sarah Johnson", description="Nurse's full name"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="Nurse's phone number"),
     *             @OA\Property(property="address", type="string", example="123 Medical Center Dr", description="Nurse's address"),
     *             @OA\Property(property="profile_picture", type="string", example="https://example.com/photo.jpg", description="URL to profile picture"),
     *             @OA\Property(property="gender", type="string", example="female", enum={"male","female"}, description="Nurse's gender")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nurse updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nurse updated successfully."),
     *             @OA\Property(property="nurse", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sarah Johnson"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="address", type="string", example="123 Medical Center Dr"),
     *                 @OA\Property(property="profile_picture", type="string", example="https://example.com/photo.jpg"),
     *                 @OA\Property(property="gender", type="string", example="female"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nurse not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/admin/nurses/{id}",
     *     summary="Delete a nurse (Admin only)",
     *     description="Delete a nurse from the system. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Nurse ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nurse deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nurse deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nurse not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $nurse = Nurse::findOrFail($id);
        $this->authorize('delete', $nurse);

        $nurse->delete();

        return response()->json(['message' => 'Nurse deleted successfully.'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/nurses/{id}/rate",
     *     summary="Rate a nurse",
     *     description="Submit a rating and comment for a nurse. Only accessible by users.",
     *     tags={"Nurses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Nurse ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="rating", type="integer", example=5, minimum=1, maximum=5, description="Rating from 1 to 5"),
     *             @OA\Property(property="comment", type="string", example="Excellent care provided", description="Optional comment about the nurse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rating submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rating submitted successfully."),
     *             @OA\Property(property="rating", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="nurse_id", type="integer", example=1),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="comment", type="string", example="Excellent care provided"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nurse not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or already rated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You have already rated this nurse."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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