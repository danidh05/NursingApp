<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Contact Us",
 *     description="API Endpoints for Contact Us form submission"
 * )
 */
class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/contact",
     *     summary="Submit contact form",
     *     description="Submit a contact form with user information and problem description. Authentication required.",
     *     tags={"Contact Us"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "second_name", "address", "description"},
     *             @OA\Property(property="first_name", type="string", example="John", description="First name"),
     *             @OA\Property(property="second_name", type="string", example="Doe", description="Second name"),
     *             @OA\Property(property="address", type="string", example="123 Main St, New York, NY", description="Full address"),
     *             @OA\Property(property="description", type="string", example="I need nursing care for my elderly mother", description="Description of the problem or inquiry"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="Phone number (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact form submitted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact form submitted successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="second_name", type="string", example="Doe"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="address", type="string", example="123 Main St, New York, NY"),
     *                 @OA\Property(property="description", type="string", example="I need nursing care for my elderly mother"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contactDTO = $this->contactService->createContact($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Contact form submitted successfully',
            'data' => $contactDTO->toArray()
        ], 201);
    }
} 