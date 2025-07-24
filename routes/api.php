<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\ServiceController; 
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\Api\SliderController as ApiSliderController;
use App\Http\Controllers\Api\PopupController as ApiPopupController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\PopupController as AdminPopupController;
use App\Http\Controllers\Admin\ServiceAreaPriceController;
use App\Http\Controllers\AreaController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
// Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
// In routes/api.php
Route::post('/verify-sms', [AuthController::class, 'verifySms']);//new

Route::post('/login', [AuthController::class, 'login']);

// Public area routes
Route::get('/areas', [AreaController::class, 'index']);

// Temporary debug routes - REMOVE AFTER TESTING
Route::get('/test-firebase', function () {
    try {
        $firebase = app(\App\Services\FirebaseStorageService::class);
        $bucket = \Kreait\Laravel\Firebase\Facades\Firebase::storage()->getBucket();
        
        return response()->json([
            'success' => true,
            'bucket_name' => $bucket->name(),
            'bucket_exists' => $bucket->exists(),
            'project_id' => config('firebase.projects.app.storage.default_bucket'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});



Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);

// ** Password Reset Routes **
Route::post('/send-password-reset-otp', [AuthController::class, 'sendPasswordResetOTP']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// Protected routes (require auth and verified email)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']); // Logout route

    // Routes for authenticated users
    Route::get('/me', [UserController::class, 'show']); // Fetch authenticated user's details
    Route::put('/users/{id}', [UserController::class, 'update']); // Update user details

    // Notifications routes for authenticated users
    Route::get('/notifications', [NotificationsController::class, 'index']); // List notifications
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']); // Mark a notification as read
    Route::delete('/notifications/{id}', [NotificationsController::class, 'destroy']); // Delete a notification
    
    // Routes accessible by both users and admins
    Route::get('/nurses', [NurseController::class, 'index']); // View all nurses
    Route::get('/nurses/{id}', [NurseController::class, 'show']); // View a specific nurse's details

    // Services accessible by both users and admins
    Route::get('/services', [ServiceController::class, 'index']); // List all services
    Route::get('/services/{service}', [ServiceController::class, 'show']); // View a specific service's details

    Route::get('/requests', [RequestController::class, 'index']); // List all requests
    Route::get('/requests/{id}', [RequestController::class, 'show']); // Show a specific request
    
    // Users and admins can view categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::get('/about', [AboutController::class, 'index']);//new
    
    // Content APIs accessible by both users and admins
    Route::get('/sliders', [ApiSliderController::class, 'index']); // Homepage sliders
    Route::get('/popups', [ApiPopupController::class, 'index']); // App launch popups
    
    // Routes specific to "user" role
    Route::middleware('role:user')->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard']);
        Route::post('/submit-location', [UserController::class, 'submitLocationOnFirstLogin']);
       

        // Request management for users
        Route::post('/requests', [RequestController::class, 'store']); // Create a new request
  

        Route::post('/nurses/{id}/rate', [NurseController::class, 'rate']);//new
    });
    
    // Routes specific to "admin" role
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Admin dashboard
        Route::get('/dashboard', [UserController::class, 'adminDashboard']); // Admin dashboard
        
        // User management routes
        Route::get('/users', [UserController::class, 'index']); // List all users
        Route::get('/users/{id}', [UserController::class, 'show']); // Fetch specific user details by ID
        Route::post('/users', [UserController::class, 'store']); // Create a new user
        Route::delete('/users/{id}', [UserController::class, 'destroy']); // Delete user

        // Nurse management routes
        Route::post('/nurses', [NurseController::class, 'store']); // Create a new nurse
        Route::put('/nurses/{id}', [NurseController::class, 'update']); // Update a nurse
        Route::delete('/nurses/{id}', [NurseController::class, 'destroy']); // Delete a nurse
        
        // Request management routes
        Route::put('/requests/{id}', [RequestController::class, 'update']); // Update a request
        Route::delete('/requests/{id}', [RequestController::class, 'destroy']); // Delete a request

        // Service management routes
        Route::post('/services', [ServiceController::class, 'store']); // Create a new service
        Route::put('/services/{service}', [ServiceController::class, 'update']); // Update a service
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']); // Delete a service

        // Category management routes
        Route::post('/categories', [CategoryController::class, 'store']);    // Create a new category
        Route::put('/categories/{category}', [CategoryController::class, 'update']);  // Update a category
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']); // Delete a category

        Route::put('/about', [AboutController::class, 'update']);//new

        // Slider management routes
        Route::get('/sliders', [AdminSliderController::class, 'index']); // List all sliders
        Route::post('/sliders', [AdminSliderController::class, 'store']); // Create a new slider
        Route::get('/sliders/{id}', [AdminSliderController::class, 'show']); // Get a specific slider
        Route::put('/sliders/{id}', [AdminSliderController::class, 'update']); // Update a slider
        Route::delete('/sliders/{id}', [AdminSliderController::class, 'destroy']); // Delete a slider

        // Popup management routes
        Route::get('/popups', [AdminPopupController::class, 'index']); // List all popups
        Route::post('/popups', [AdminPopupController::class, 'store']); // Create a new popup
        Route::get('/popups/{id}', [AdminPopupController::class, 'show']); // Get a specific popup
        Route::put('/popups/{id}', [AdminPopupController::class, 'update']); // Update a popup
        Route::delete('/popups/{id}', [AdminPopupController::class, 'destroy']); // Delete a popup

        // Service Area Price management routes
        Route::get('/service-area-prices', [ServiceAreaPriceController::class, 'index']); // List all service area prices
        Route::post('/service-area-prices', [ServiceAreaPriceController::class, 'store']); // Create a new service area price
        Route::put('/service-area-prices/{id}', [ServiceAreaPriceController::class, 'update']); // Update a service area price
        Route::delete('/service-area-prices/{id}', [ServiceAreaPriceController::class, 'destroy']); // Delete a service area price
        Route::get('/service-area-prices/service/{serviceId}', [ServiceAreaPriceController::class, 'getServicePrices']); // Get prices for a specific service
    });
});