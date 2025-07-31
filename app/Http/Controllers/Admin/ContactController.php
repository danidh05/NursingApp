<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Admin - Contact Us",
 *     description="API Endpoints for Contact Us management (Admin only)"
 * )
 */
class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/contacts",
     *     summary="Get all contact submissions",
     *     description="Retrieve all contact form submissions for admin review",
     *     tags={"Admin - Contact Us"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all contact submissions",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact submissions retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="second_name", type="string", example="Doe"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="address", type="string", example="123 Main St, New York, NY"),
     *                 @OA\Property(property="description", type="string", example="I need nursing care for my elderly mother"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="total_count", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $contacts = $this->contactService->getAllContacts();
        $totalCount = $this->contactService->getContactCount();
        
        return response()->json([
            'success' => true,
            'message' => 'Contact submissions retrieved successfully',
            'data' => $contacts->map(fn($contact) => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'second_name' => $contact->second_name,
                'full_name' => $contact->full_name,
                'address' => $contact->address,
                'description' => $contact->description,
                'phone_number' => $contact->phone_number,
                'created_at' => $contact->created_at->toISOString(),
                'updated_at' => $contact->updated_at->toISOString(),
            ]),
            'total_count' => $totalCount
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/contacts/{id}",
     *     summary="Get specific contact submission",
     *     description="Retrieve a specific contact form submission with detailed information",
     *     tags={"Admin - Contact Us"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact submission details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact submission retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contact", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="second_name", type="string", example="Doe"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="address", type="string", example="123 Main St, New York, NY"),
     *                     @OA\Property(property="description", type="string", example="I need nursing care for my elderly mother"),
     *                     @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 ),
     *                 @OA\Property(property="created_at_formatted", type="string", example="July 31, 2025 at 2:30 PM"),
     *                 @OA\Property(property="days_ago", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contact submission not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $contactDetails = $this->contactService->getContactWithDetails($id);
        
        if (!$contactDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Contact submission not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact submission retrieved successfully',
            'data' => $contactDetails
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/contacts/{id}",
     *     summary="Delete contact submission",
     *     description="Delete a contact form submission",
     *     tags={"Admin - Contact Us"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact submission deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact submission deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contact submission not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->contactService->deleteContact($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Contact submission not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact submission deleted successfully'
        ]);
    }
} 