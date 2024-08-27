<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Update the user's location on first login.
     */
    public function submitLocationOnFirstLogin(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();

        // Only allow location update on first login
        if ($user->is_first_login) {
            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            $user->is_first_login = false; // Mark as no longer first login
            $user->save();

            return response()->json(['message' => 'Location saved successfully'], 200);
        }

        return response()->json(['message' => 'Location already set'], 400);
    }
}