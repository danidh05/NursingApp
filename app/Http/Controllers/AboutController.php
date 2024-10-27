<?php

namespace App\Http\Controllers;

use App\Models\About;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import the correct trait for authorization checks


class AboutController extends Controller
{
    use AuthorizesRequests; // Use the AuthorizesRequests trait to enable the use of `authorize` method

    /**
     * Display the About Us information.
     */
    public function index()
    {
        $about = About::first(); // Fetch the first (or latest) about record

        return response()->json(['about' => $about], 200);
    }

    /**
     * Update the About Us information (Admin only).
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