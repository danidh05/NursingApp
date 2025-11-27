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

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Register a new user with name, email, phone number, and password. Sends OTP via WhatsApp for verification.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone_number","password","password_confirmation","area_id"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123", description="User's password"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123", description="Password confirmation"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15", description="User's birth date (YYYY-MM-DD) - optional, used for birthday notifications"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "none"}, example="male", description="User's gender - optional, one of: male, female, or none"),
     *             @OA\Property(property="area_id", type="integer", example=1, description="User's area/region ID (required for region-based pricing)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully. Please check your WhatsApp for the OTP.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="OTP sending failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to send OTP. Please try again later.")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,none',
            'area_id' => 'required|exists:areas,id',
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
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'area_id' => $request->area_id,
        ]);
    
        if (!$this->twilioService->sendVerificationCode($user->phone_number)) {
            // If OTP sending fails, delete the created user and return an error
            $user->delete();
            return response()->json(['error' => 'Failed to send OTP. Please try again later.'], 500);
        }
    
        // Return a response indicating that the user needs to confirm their phone number
        return response()->json(['message' => 'User registered successfully. Please check your WhatsApp for the OTP.'], 201);
    }
    
    /**
     * @OA\Post(
     *     path="/api/verify-sms",
     *     summary="Verify SMS/WhatsApp OTP",
     *     description="Verify the OTP sent to user's phone number via WhatsApp/SMS",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number","verification_code"},
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number"),
     *             @OA\Property(property="verification_code", type="string", example="123456", description="OTP code received via WhatsApp/SMS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phone number verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Phone number successfully verified."),
     *             @OA\Property(property="token", type="string", example="1|abc123...", description="Bearer token for API access"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="role_id", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid OTP or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired OTP.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="User login",
     *     description="Authenticate user with email and password",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", example="password123", description="User's password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|abc123...", description="Bearer token for API access"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="role_id", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Phone not verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your phone number is not verified.")
     *         )
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

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="User logout",
     *     description="Logout user and invalidate current token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

      // Send OTP for password reset
      /**
     * @OA\Post(
     *     path="/api/send-password-reset-otp",
     *     summary="Send password reset OTP",
     *     description="Send OTP to user's phone number for password reset",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number"},
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP sent successfully. Please check your phone.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User with this phone number does not exist.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="OTP sending failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to send OTP. Please try again later.")
     *         )
     *     )
     * )
     */
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
  
      /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset password with OTP",
     *     description="Reset user password using OTP verification",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number","token","password","password_confirmation"},
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number"),
     *             @OA\Property(property="token", type="string", example="123456", description="OTP token received via SMS/WhatsApp"),
     *             @OA\Property(property="password", type="string", minLength=8, example="newpassword123", description="New password"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid OTP or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired OTP."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/resend-verification-code",
     *     summary="Resend verification code",
     *     description="Resend OTP verification code to user's phone number",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number"},
     *             @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification code resent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification code resent successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Phone already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Phone number is already verified.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User with this phone number does not exist.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="OTP sending failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to send OTP. Please try again later.")
     *         )
     *     )
     * )
     */
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