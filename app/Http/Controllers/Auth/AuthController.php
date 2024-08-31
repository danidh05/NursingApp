<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Mail\Message; // Import the correct class
use Illuminate\Mail\SentMessage; // Correct class for handling raw email assertions
use App\Mail\ConfirmationCodeMail;



class AuthController extends Controller
{
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
     
         // Assign the 'user' role by default (role_id = 2)
         $role_id = 2; // Assuming 2 is the ID for the 'user' role
     
         // Create the user with the 'user' role by default
         $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
             'phone_number' => $request->phone_number,
             'password' => Hash::make($request->password),
             'role_id' => $role_id, // Automatically set role_id to 2
         ]);
     
         // Generate a confirmation code
         $confirmationCode = mt_rand(100000, 999999);
     
         // Save the confirmation code and its expiration time
         $user->confirmation_code = $confirmationCode;
         $user->confirmation_code_expires_at = Carbon::now()->addMinutes(10); // Code expires in 10 minutes
         $user->save();
     
         try {
            Mail::to($user->email)->send(new ConfirmationCodeMail($confirmationCode));
        } catch (\Exception $e) {
            dd($e->getMessage()); // This will output the error message to the screen
            \Log::error('Failed to send mailable: ' . $e->getMessage());
            return response()->json(['message' => 'Registration successful, but failed to send confirmation email. Please try again later.'], 500);
        }
        
        
     
         // Return a response indicating that the user needs to confirm their email
         return response()->json(['message' => 'User registered successfully. Please check your email for the confirmation code.'], 201);
     }
     
    

    /**
     * Handle email verification.
     */
    public function verifyEmail(Request $request)
    {
        // Validate the email and confirmation code
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'confirmation_code' => 'required|string',
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user by email and confirmation code
        $user = User::where('email', $request->email)
            ->where('confirmation_code', $request->confirmation_code)
            ->first();

        // Check if the confirmation code is invalid or expired
        if (!$user || Carbon::now()->isAfter($user->confirmation_code_expires_at)) {
            return response()->json(['message' => 'Invalid or expired confirmation code.'], 422);
        }

        // Mark the email as verified
        $user->email_verified_at = Carbon::now();
        $user->confirmation_code = null; // Clear the confirmation code
        $user->confirmation_code_expires_at = null; // Clear the expiration time
        $user->save();

        // Return a success message
        return response()->json(['message' => 'Email successfully verified.'], 200);
    }

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
    
            // Check if the user's email is verified
            if (!$user->email_verified_at) {
                return response()->json(['message' => 'Your email is not verified.'], 403);
            }
    
            // Create an API token
            $token = $user->createToken('authToken')->plainTextToken;
    
            return response()->json(['token' => $token], 200);
        }
    
        // Invalid credentials
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    
}