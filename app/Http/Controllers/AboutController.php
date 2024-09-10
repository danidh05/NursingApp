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
        $about = About::firstOrFail(); // Ensure the about record exists
    
        $this->authorize('update', $about); // Authorize the update action
    
        $validatedData = $request->validate([
            'online_shop_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'whatsapp_number' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
    
        $about->update($validatedData);
    
        return response()->json(['message' => 'About Us updated successfully.', 'about' => $about], 200);
    }
    
}