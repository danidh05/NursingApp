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
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\PopupController as AdminPopupController;
use App\Http\Controllers\Admin\ServiceAreaPriceController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\ContactController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
// Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
// In routes/api.php
Route::post('/verify-sms', [AuthController::class, 'verifySms']);//new

Route::post('/login', [AuthController::class, 'login']);

// Public area routes
Route::get('/areas', [AreaController::class, 'index']);

// Public settings route (accessible without authentication)
Route::get('/settings/public', [\App\Http\Controllers\SettingsController::class, 'getPublic']); // Get public settings for frontend

// Authenticated routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Contact form submission (requires authentication)
    Route::post('/contact', [ContactController::class, 'store']);
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
    
    // Routes accessible by both users and admins (with translation support)
    Route::get('/nurses', [NurseController::class, 'index']); // View all nurses
    Route::get('/nurses/{id}', [NurseController::class, 'show']); // View a specific nurse's details

    // Services accessible by both users and admins (with translation support)
    Route::middleware(['auth:sanctum', 'detect.language'])->group(function () {
        Route::get('/services', [ServiceController::class, 'index']); // List all services
        Route::get('/services/area/{area_id}', [ServiceController::class, 'getServicesByArea']); // Get all services for a specific area with pricing
        Route::get('/services/quote', [ServiceController::class, 'quote']); // Get pricing quote for services in specific area
        Route::get('/services/{service}', [ServiceController::class, 'show']); // View a specific service's details
        
        // FAQ APIs accessible by both users and admins (with translation support)
        Route::get('/faqs', [FAQController::class, 'index']); // List all active FAQs
        Route::get('/faqs/{id}', [FAQController::class, 'show']); // Get a specific FAQ
    });

    Route::get('/requests', [RequestController::class, 'index']); // List all requests
    Route::get('/requests/default-area', [RequestController::class, 'getDefaultArea']); // Get user's default area for request creation
    Route::get('/requests/{id}', [RequestController::class, 'show']); // Show a specific request
    
    // Users and admins can view categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::get('/about', [AboutController::class, 'index']);//new
    
    // Content APIs accessible by both users and admins
    Route::get('/sliders', [ApiSliderController::class, 'index']); // Homepage sliders
    Route::get('/popups', [ApiPopupController::class, 'index']); // App launch popups
    
    // Stream.io Chat APIs
    Route::get('/stream/token', [StreamController::class, 'getToken']); // Get Stream.io token for authenticated user
    Route::post('/stream/users', [StreamController::class, 'createUser']); // Create user in Stream.io
    Route::put('/stream/users', [StreamController::class, 'updateUser']); // Update user in Stream.io
    Route::post('/stream/channels', [StreamController::class, 'createChannel']); // Create channel in Stream.io
    Route::post('/stream/channels/members', [StreamController::class, 'addChannelMembers']); // Add members to channel
    Route::post('/stream/messages', [StreamController::class, 'sendMessage']); // Send message to channel
    
    // Routes specific to "user" role
    Route::middleware('role:user')->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard']);
        Route::post('/submit-location', [UserController::class, 'submitLocationOnFirstLogin']);
       

        // Request management for users
        Route::post('/requests', [RequestController::class, 'store']); // Create a new request
  

        Route::post('/nurses/{id}/rate', [NurseController::class, 'rate']);//new

        // Contact management for users
        // Route::post('/contact', [ContactController::class, 'store']); // Removed duplicate
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
        Route::post('/sliders/reorder', [AdminSliderController::class, 'reorder']); // Reorder sliders

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

        // User Request History (for discount decisions)
        Route::get('/users/{userId}/requests', [\App\Http\Controllers\Admin\UserRequestController::class, 'getUserRequests']); // View user's request history

        // Custom Notification management routes
        Route::post('/notifications/custom', [\App\Http\Controllers\Admin\CustomNotificationController::class, 'store']); // Send custom notification to user
        Route::get('/notifications/custom', [\App\Http\Controllers\Admin\CustomNotificationController::class, 'index']); // List custom notifications sent by admin
        Route::get('/notifications/users', [\App\Http\Controllers\Admin\CustomNotificationController::class, 'getUsers']); // Get users for notification targeting

        // FAQ management routes
        Route::get('/faqs', [\App\Http\Controllers\Admin\FAQController::class, 'index']); // List all FAQs (including inactive)
        Route::post('/faqs', [\App\Http\Controllers\Admin\FAQController::class, 'store']); // Create a new FAQ
        Route::get('/faqs/{id}', [\App\Http\Controllers\Admin\FAQController::class, 'show']); // Get a specific FAQ
        Route::put('/faqs/{id}', [\App\Http\Controllers\Admin\FAQController::class, 'update']); // Update an FAQ
        Route::delete('/faqs/{id}', [\App\Http\Controllers\Admin\FAQController::class, 'destroy']); // Delete an FAQ
        Route::patch('/faqs/{id}/toggle', [\App\Http\Controllers\Admin\FAQController::class, 'toggleStatus']); // Toggle FAQ active status
        Route::post('/faqs/reorder', [\App\Http\Controllers\Admin\FAQController::class, 'reorder']); // Reorder FAQs
        
        // FAQ translation management routes
        Route::get('/faqs/{id}/translations', [\App\Http\Controllers\Admin\FAQController::class, 'getTranslations']); // Get all translations for an FAQ
        Route::post('/faqs/{id}/translations', [\App\Http\Controllers\Admin\FAQController::class, 'addTranslation']); // Add translation to FAQ
        Route::put('/faqs/{id}/translations/{locale}', [\App\Http\Controllers\Admin\FAQController::class, 'updateTranslation']); // Update FAQ translation
        Route::delete('/faqs/{id}/translations/{locale}', [\App\Http\Controllers\Admin\FAQController::class, 'deleteTranslation']); // Delete FAQ translation

        // Area management routes
        Route::get('/areas', [\App\Http\Controllers\Admin\AreaController::class, 'index']); // List all areas with user counts
        Route::post('/areas', [\App\Http\Controllers\Admin\AreaController::class, 'store']); // Create a new area
        Route::get('/areas/{id}', [\App\Http\Controllers\Admin\AreaController::class, 'show']); // Get area with details
        Route::put('/areas/{id}', [\App\Http\Controllers\Admin\AreaController::class, 'update']); // Update an area
        Route::delete('/areas/{id}', [\App\Http\Controllers\Admin\AreaController::class, 'destroy']); // Delete an area

        // Contact management routes
        Route::get('/contacts', [\App\Http\Controllers\Admin\ContactController::class, 'index']); // List all contact submissions
        Route::get('/contacts/{id}', [\App\Http\Controllers\Admin\ContactController::class, 'show']); // Get specific contact submission
        Route::delete('/contacts/{id}', [\App\Http\Controllers\Admin\ContactController::class, 'destroy']); // Delete contact submission

        // Settings management routes (admin only)
        Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index']); // List all settings
        Route::get('/settings/{id}', [\App\Http\Controllers\SettingsController::class, 'show']); // Get specific setting
        Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'store']); // Create new setting
        Route::put('/settings/{id}', [\App\Http\Controllers\SettingsController::class, 'update']); // Update setting
        Route::delete('/settings/{id}', [\App\Http\Controllers\SettingsController::class, 'destroy']); // Delete setting
        Route::post('/settings/{id}/toggle', [\App\Http\Controllers\SettingsController::class, 'toggleActive']); // Toggle setting active status

            });

    // Temporary request-scoped chat (feature gated) - accessible to both users and admins
    Route::post('/requests/{id}/chat/open', [\App\Http\Controllers\ChatController::class, 'open']);
    Route::get('/chat/threads/{threadId}/messages', [\App\Http\Controllers\ChatController::class, 'listMessages']);
    Route::post('/chat/threads/{threadId}/messages', [\App\Http\Controllers\ChatController::class, 'postMessage']);
    Route::patch('/chat/threads/{threadId}/close', [\App\Http\Controllers\ChatController::class, 'close']);
});