<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\TwilioService;

class AuthController extends Controller
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    // Handle user registration
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
    
        // Set role_id explicitly to 'user' if not provided
        $role_id = Role::where('name', 'user')->first()->id ?? 2;
    
        // Create the user with the 'user' role by default
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role_id' => $role_id,
        ]);
    
        if (!$this->twilioService->sendVerificationCode($user->phone_number)) {
            // If OTP sending fails, delete the created user and return an error
            $user->delete();
            return response()->json(['error' => 'Failed to send OTP. Please try again later.'], 500);
        }
            
        // Return a response indicating that the user needs to confirm their phone number
        return response()->json(['message' => 'User registered successfully. Please check your WhatsApp for the OTP.'], 201);
    }

    // Handle WhatsApp OTP verification
    public function verifySms(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'verification_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user based on phone number
        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Verify the OTP using Twilio Verify API
        if ($this->twilioService->verifyCode($request->phone_number, $request->verification_code)) {
            // Mark the phone as verified by updating the email_verified_at field
            $user->email_verified_at = Carbon::now();
            $user->save();

            // Generate an API token for the user
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'message' => 'Phone number successfully verified.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role_id' => $user->role_id,
                ],
            ], 200);
        } else {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }
    }

    // Handle user login
    public function login(Request $request)
    {
        // Validate login credentials
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (!$user->email_verified_at) {
                return response()->json(['message' => 'Your phone number is not verified.'], 403);
            }

            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                ],
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Handle user logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    // Send OTP for password reset
    public function sendPasswordResetOTP(Request $request)
    {
        // Validate request (phone number is required)
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find user by phone number
        $user = User::where('phone_number', $request->phone_number)->first();
        
        if (!$user) {
            return response()->json(['message' => 'User with this phone number does not exist.'], 404);
        }

        // Generate token
        $token = Str::random(6);  // OTP code of 6 characters
        $expiry = Carbon::now()->addMinutes(10);  // Token expires in 10 minutes

        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone_number' => $request->phone_number],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        // Send OTP via Twilio
        if (!$this->twilioService->sendVerificationCode($user->phone_number)) {
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        return response()->json(['message' => 'OTP sent successfully. Please check your phone.'], 200);
    }

    // Verify OTP and Reset Password
    public function resetPassword(Request $request)
    {
        // Validate request (phone_number, token, and new password)
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Verify OTP via Twilio
        if (!$this->twilioService->verifyCode($request->phone_number, $request->token)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        // Find user by phone number
        $user = User::where('phone_number', $request->phone_number)->first();
        
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }

    // Resend verification code
    public function resendVerificationCode(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find user by phone number
        $user = User::where('phone_number', $request->phone_number)->first();
        
        if (!$user) {
            return response()->json(['message' => 'User with this phone number does not exist.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Phone number is already verified.'], 400);
        }

        // Send OTP via Twilio
        if (!$this->twilioService->sendVerificationCode($user->phone_number)) {
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        return response()->json(['message' => 'Verification code resent successfully.'], 200);
    }
}