<?php

namespace App\Http\Controllers;

use App\Models\About;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import the correct trait for authorization checks


class AboutController extends Controller
{
    use AuthorizesRequests; // Use the AuthorizesRequests trait to enable the use of `authorize` method

    /**
     * @OA\Get(
     *     path="/api/about",
     *     summary="Get about information",
     *     description="Retrieve the about us information. Available to both users and admins.",
     *     tags={"About"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="About information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="about", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="online_shop_url", type="string", example="https://shop.example.com"),
     *                 @OA\Property(property="facebook_url", type="string", example="https://facebook.com/company"),
     *                 @OA\Property(property="instagram_url", type="string", example="https://instagram.com/company"),
     *                 @OA\Property(property="tiktok_url", type="string", example="https://tiktok.com/@company"),
     *                 @OA\Property(property="whatsapp_numbers", type="array", @OA\Items(type="string"), example={"+1234567890", "+0987654321"}),
     *                 @OA\Property(property="description", type="string", example="We provide professional nursing care services"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
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
        $about = About::first(); // Fetch the first (or latest) about record

        return response()->json(['about' => $about], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/about",
     *     summary="Update about information (Admin only)",
     *     description="Update the about us information. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="online_shop_url", type="string", example="https://shop.example.com", description="Online shop URL"),
     *             @OA\Property(property="facebook_url", type="string", example="https://facebook.com/company", description="Facebook page URL"),
     *             @OA\Property(property="instagram_url", type="string", example="https://instagram.com/company", description="Instagram profile URL"),
     *             @OA\Property(property="tiktok_url", type="string", example="https://tiktok.com/@company", description="TikTok profile URL"),
     *             @OA\Property(property="whatsapp_numbers", type="array", @OA\Items(type="string"), example={"+1234567890", "+0987654321"}, description="Array of WhatsApp numbers"),
     *             @OA\Property(property="description", type="string", example="We provide professional nursing care services", description="About description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="About information updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="About Us updated successfully."),
     *             @OA\Property(property="about", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="online_shop_url", type="string", example="https://shop.example.com"),
     *                 @OA\Property(property="facebook_url", type="string", example="https://facebook.com/company"),
     *                 @OA\Property(property="instagram_url", type="string", example="https://instagram.com/company"),
     *                 @OA\Property(property="tiktok_url", type="string", example="https://tiktok.com/@company"),
     *                 @OA\Property(property="whatsapp_numbers", type="array", @OA\Items(type="string"), example={"+1234567890", "+0987654321"}),
     *                 @OA\Property(property="description", type="string", example="We provide professional nursing care services"),
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
     *         description="About information not found"
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
    public function update(Request $request)
    {
        $about = About::firstOrFail();
        $this->authorize('update', $about);
    
        $validatedData = $request->validate([
            'online_shop_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'whatsapp_numbers' => 'nullable|array', // Validate as array
            'whatsapp_numbers.*' => 'nullable|string', // Each item in the array should be a string
            'description' => 'nullable|string',
            'tiktok_url' => 'nullable|url', // New field for TikTok URL
        ]);
    
        $about->update($validatedData);
    
        return response()->json(['message' => 'About Us updated successfully.', 'about' => $about], 200);
    }
}