<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\InfobipService; // Import the Infobip service
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http; // Correct import for Http requests
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $infobipService;

    public function __construct(InfobipService $infobipService)
    {
        $this->infobipService = $infobipService;
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Set role_id explicitly to 'user' if not provided (assuming 'user' role is id 2)
        $role_id = Role::where('name', 'user')->first()->id ?? 2; // Default to 'user' role

        // Create the user with the 'user' role by default
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role_id' => $role_id, // Automatically set role_id to 'user' if not provided
        ]);

        // Generate a confirmation code
        $confirmationCode = mt_rand(100000, 999999);

        // Save the confirmation code and its expiration time
        $user->confirmation_code = $confirmationCode;
        $user->confirmation_code_expires_at = Carbon::now()->addMinutes(10); // Code expires in 10 minutes
        $user->save();

        // Send the confirmation code via SMS using Infobip
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App YOUR_INFOPBIP_API_KEY',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://peykq3.api.infobip.com/sms/2/text/advanced', [
                'messages' => [
                    [
                        'destinations' => [
                            ['to' => $user->phone_number],
                        ],
                        'from' => 'SenderID', // Replace with your sender ID
                        'text' => "Your confirmation code is: $confirmationCode",
                    ],
                ],
            ]);

            if ($response->failed()) {
                return response()->json(['message' => 'Registration successful, but failed to send confirmation SMS. Please try again later.'], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send SMS: ' . $e->getMessage());
            return response()->json(['message' => 'Registration successful, but failed to send confirmation SMS. Please try again later.'], 500);
        }

        // Return a response indicating that the user needs to confirm their phone number
        return response()->json(['message' => 'User registered successfully. Please check your SMS for the confirmation code.'], 201);
    }


    /**
     * Handle SMS verification.
     */
    public function verifySms(Request $request)
    {
        // Validate the phone number and confirmation code
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'confirmation_code' => 'required|string',
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user by phone number and confirmation code
        $user = User::where('phone_number', $request->phone_number)
            ->where('confirmation_code', $request->confirmation_code)
            ->first();

        // Check if the confirmation code is invalid or expired
        if (!$user || Carbon::now()->isAfter($user->confirmation_code_expires_at)) {
            return response()->json(['message' => 'Invalid or expired confirmation code.'], 422);
        }

        // Mark the user as verified
        $user->email_verified_at = Carbon::now(); // Change this to a relevant field if different
        $user->confirmation_code = null; // Clear the confirmation code
        $user->confirmation_code_expires_at = null; // Clear the expiration time
        $user->save();

        // Generate an API token for the user
        $token = $user->createToken('authToken')->plainTextToken;

        // Return a success message along with the token
        return response()->json([
            'message' => 'Phone number successfully verified.',
            'token' => $token, // Include the token in the response
        ], 200);
    }

    /**
     * Handle user login.
     */
/**
 * Handle user login.
 */
public function login(Request $request)
{
    // Validate login credentials
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    // Return validation errors, if any
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $credentials = $request->only('email', 'password');

    // Attempt to log the user in
    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // Check if the user's phone number is verified
        if (!$user->email_verified_at) { // Adjust field name if you use a different one for SMS verification
            return response()->json(['message' => 'Your phone number is not verified.'], 403);
        }

        // Create an API token
        $token = $user->createToken('authToken')->plainTextToken;

        // Return the token, user details including role_id
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id, // Include role_id to direct the user to the correct dashboard
            ],
        ], 200);
    }

    // Invalid credentials
    return response()->json(['message' => 'Invalid credentials'], 401);
}


    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
    /**
     * Test SMS sending functionality.
     */
    public function sendTestSms(Request $request)
    {
        // Validate the request data
        $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string',
        ]);

        // Extract phone number and message from the request
        $phoneNumber = $request->input('phone_number');
        $message = $request->input('message');

        // Send SMS using InfobipService
        $response = $this->infobipService->sendSms($phoneNumber, $message);

        if ($response) {
            return response()->json(['message' => 'SMS sent successfully!', 'response' => $response], 200);
        } else {
            return response()->json(['message' => 'Failed to send SMS.'], 500);
        }
    }

}