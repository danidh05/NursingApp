<?php

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
use App\Http\Controllers\Admin\RayAreaPriceController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TestPackageController;
use App\Http\Controllers\Admin\TestController as AdminTestController;
use App\Http\Controllers\Admin\TestPackageController as AdminTestPackageController;
use App\Http\Controllers\RayController;
use App\Http\Controllers\Admin\RayController as AdminRayController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\Admin\MachineController as AdminMachineController;
use App\Http\Controllers\Admin\MachineAreaPriceController;
use App\Http\Controllers\PhysiotherapistController;
use App\Http\Controllers\Admin\PhysiotherapistController as AdminPhysiotherapistController;
use App\Http\Controllers\Admin\PhysiotherapistAreaPriceController;
use App\Http\Controllers\Admin\PhysioMachineController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\OfferAreaPriceController;
use App\Http\Controllers\Admin\NurseVisitController as AdminNurseVisitController;
use App\Http\Controllers\Admin\DutyController as AdminDutyController;
use App\Http\Controllers\Admin\BabysitterController as AdminBabysitterController;
use Illuminate\Support\Facades\Broadcast;



Broadcast::routes(['middleware' => ['auth:sanctum']]);

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
        
        // Tests and Test Packages (Category 2)
        Route::get('/tests', [TestController::class, 'index']); // List all tests
        Route::get('/tests/{id}', [TestController::class, 'show']); // View a specific test
        Route::get('/test-packages', [TestPackageController::class, 'index']); // List all test packages
        Route::get('/test-packages/{id}', [TestPackageController::class, 'show']); // View a specific test package
        
        // Rays (Category 3)
        Route::get('/rays', [RayController::class, 'index']); // List all rays
        Route::get('/rays/area/{area_id}', [RayController::class, 'getRaysByArea']); // Get all rays for a specific area with pricing
        Route::get('/rays/{id}', [RayController::class, 'show']); // View a specific ray
        
        // Machines (Category 4)
        Route::get('/machines', [MachineController::class, 'index']); // List all machines
        Route::get('/machines/area/{area_id}', [MachineController::class, 'getMachinesByArea']); // Get all machines for a specific area with pricing
        Route::get('/machines/{id}', [MachineController::class, 'show']); // View a specific machine
        
        // Physiotherapists (Category 5)
        Route::get('/physiotherapists', [PhysiotherapistController::class, 'index']); // List all physiotherapists
        Route::get('/physiotherapists/area/{area_id}', [PhysiotherapistController::class, 'getPhysiotherapistsByArea']); // Get all physiotherapists for a specific area with pricing
        Route::get('/physiotherapists/{id}', [PhysiotherapistController::class, 'show']); // View a specific physiotherapist
        
        // Physio Machines (for Category 5) - User accessible
        Route::get('/physio-machines', [\App\Http\Controllers\PhysioMachineController::class, 'index']); // List all physio machines
        Route::get('/physio-machines/{id}', [\App\Http\Controllers\PhysioMachineController::class, 'show']); // View a specific physio machine
        
        // Offers (Category 6)
        Route::get('/offers', [OfferController::class, 'index']); // List all offers
        Route::get('/offers/area/{area_id}', [OfferController::class, 'getOffersByArea']); // Get all offers for a specific area with pricing
        Route::get('/offers/{id}', [OfferController::class, 'show']); // View a specific offer
        
        // Duties (Category 7) - Nurse Visits, Duties, Babysitters
        Route::get('/nurse-visits', [\App\Http\Controllers\NurseVisitController::class, 'index']); // List all nurse visits
        Route::get('/nurse-visits/area/{area_id}', [\App\Http\Controllers\NurseVisitController::class, 'getNurseVisitsByArea']); // Get all nurse visits for a specific area
        Route::get('/nurse-visits/{id}', [\App\Http\Controllers\NurseVisitController::class, 'show']); // View a specific nurse visit
        Route::post('/nurse-visits/calculate-price', [\App\Http\Controllers\NurseVisitController::class, 'calculatePrice']); // Calculate price for nurse visits
        
        Route::get('/duties', [\App\Http\Controllers\DutyController::class, 'index']); // List all duties
        Route::get('/duties/area/{area_id}', [\App\Http\Controllers\DutyController::class, 'getDutiesByArea']); // Get all duties for a specific area
        Route::get('/duties/{id}', [\App\Http\Controllers\DutyController::class, 'show']); // View a specific duty
        Route::post('/duties/calculate-price', [\App\Http\Controllers\DutyController::class, 'calculatePrice']); // Calculate price for duties
        
        Route::get('/babysitters', [\App\Http\Controllers\BabysitterController::class, 'index']); // List all babysitters
        Route::get('/babysitters/area/{area_id}', [\App\Http\Controllers\BabysitterController::class, 'getBabysittersByArea']); // Get all babysitters for a specific area
        Route::get('/babysitters/{id}', [\App\Http\Controllers\BabysitterController::class, 'show']); // View a specific babysitter
        Route::post('/babysitters/calculate-price', [\App\Http\Controllers\BabysitterController::class, 'calculatePrice']); // Calculate price for babysitters
        
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
        Route::match(['put', 'post'], '/services/{service}', [ServiceController::class, 'update'])->name('admin.services.update'); // Update a service (supports POST with _method=PUT for file uploads)
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']); // Delete a service
        
        // Test management routes (Category 2)
        // Note: apiResource creates PUT routes, but we need POST support for file uploads with method spoofing
        Route::get('/tests', [AdminTestController::class, 'index']);
        Route::post('/tests', [AdminTestController::class, 'store']);
        Route::get('/tests/{test}', [AdminTestController::class, 'show']);
        Route::match(['put', 'post'], '/tests/{test}', [AdminTestController::class, 'update'])->name('admin.tests.update'); // Supports POST with _method=PUT for file uploads
        Route::delete('/tests/{test}', [AdminTestController::class, 'destroy']);
        
        Route::get('/test-packages', [AdminTestPackageController::class, 'index']);
        Route::post('/test-packages', [AdminTestPackageController::class, 'store']);
        Route::get('/test-packages/{testPackage}', [AdminTestPackageController::class, 'show']);
        Route::match(['put', 'post'], '/test-packages/{testPackage}', [AdminTestPackageController::class, 'update'])->name('admin.test-packages.update'); // Supports POST with _method=PUT for file uploads
        Route::delete('/test-packages/{testPackage}', [AdminTestPackageController::class, 'destroy']);

        // Rays management (Category 3)
        Route::get('/rays', [AdminRayController::class, 'index']);
        Route::post('/rays', [AdminRayController::class, 'store']);
        Route::get('/rays/{ray}', [AdminRayController::class, 'show']);
        Route::match(['put', 'post'], '/rays/{ray}', [AdminRayController::class, 'update'])->name('admin.rays.update'); // Supports POST with _method=PUT for file uploads
        Route::delete('/rays/{ray}', [AdminRayController::class, 'destroy']);
        
        // Ray Area Price management routes
        Route::get('/ray-area-prices', [RayAreaPriceController::class, 'index']); // List all ray area prices
        Route::post('/ray-area-prices', [RayAreaPriceController::class, 'store']); // Create a new ray area price
        Route::put('/ray-area-prices/{id}', [RayAreaPriceController::class, 'update']); // Update a ray area price
        Route::delete('/ray-area-prices/{id}', [RayAreaPriceController::class, 'destroy']); // Delete a ray area price
        Route::get('/ray-area-prices/ray/{rayId}', [RayAreaPriceController::class, 'getRayPrices']); // Get prices for a specific ray
        
        // Machines management (Category 4)
        Route::get('/machines', [AdminMachineController::class, 'index']);
        Route::post('/machines', [AdminMachineController::class, 'store']);
        Route::get('/machines/{machine}', [AdminMachineController::class, 'show']);
        Route::match(['put', 'post'], '/machines/{machine}', [AdminMachineController::class, 'update'])->name('admin.machines.update'); // Supports POST with _method=PUT for file uploads
        Route::delete('/machines/{machine}', [AdminMachineController::class, 'destroy']);
        
        // Machine Area Price management routes
        Route::get('/machine-area-prices', [MachineAreaPriceController::class, 'index']); // List all machine area prices
        Route::post('/machine-area-prices', [MachineAreaPriceController::class, 'store']); // Create a new machine area price
        Route::put('/machine-area-prices/{id}', [MachineAreaPriceController::class, 'update']); // Update a machine area price
        Route::delete('/machine-area-prices/{id}', [MachineAreaPriceController::class, 'destroy']); // Delete a machine area price
        Route::get('/machine-area-prices/machine/{machineId}', [MachineAreaPriceController::class, 'getMachinePrices']); // Get prices for a specific machine
        
        // Physiotherapists management (Category 5)
        Route::get('/physiotherapists', [AdminPhysiotherapistController::class, 'index']);
        Route::post('/physiotherapists', [AdminPhysiotherapistController::class, 'store']);
        Route::get('/physiotherapists/{physiotherapist}', [AdminPhysiotherapistController::class, 'show']);
        Route::match(['put', 'post'], '/physiotherapists/{physiotherapist}', [AdminPhysiotherapistController::class, 'update'])->name('admin.physiotherapists.update');
        Route::delete('/physiotherapists/{physiotherapist}', [AdminPhysiotherapistController::class, 'destroy']);
        
        // Physiotherapist Area Price management routes
        Route::get('/physiotherapist-area-prices', [PhysiotherapistAreaPriceController::class, 'index']);
        Route::post('/physiotherapist-area-prices', [PhysiotherapistAreaPriceController::class, 'store']);
        Route::put('/physiotherapist-area-prices/{id}', [PhysiotherapistAreaPriceController::class, 'update']);
        Route::delete('/physiotherapist-area-prices/{id}', [PhysiotherapistAreaPriceController::class, 'destroy']);
        Route::get('/physiotherapist-area-prices/physiotherapist/{physiotherapistId}', [PhysiotherapistAreaPriceController::class, 'getPhysiotherapistPrices']);
        
        // Physio Machines management (Category 5)
        Route::get('/physio-machines', [PhysioMachineController::class, 'index']);
        Route::post('/physio-machines', [PhysioMachineController::class, 'store']);
        Route::get('/physio-machines/{physioMachine}', [PhysioMachineController::class, 'show']);
        Route::put('/physio-machines/{physioMachine}', [PhysioMachineController::class, 'update']);
        Route::delete('/physio-machines/{physioMachine}', [PhysioMachineController::class, 'destroy']);
        
        // Offers management (Category 6)
        Route::get('/offers', [AdminOfferController::class, 'index']);
        Route::post('/offers', [AdminOfferController::class, 'store']);
        Route::get('/offers/{offer}', [AdminOfferController::class, 'show']);
        Route::match(['put', 'post'], '/offers/{offer}', [AdminOfferController::class, 'update'])->name('admin.offers.update');
        Route::delete('/offers/{offer}', [AdminOfferController::class, 'destroy']);
        
        // Offer Area Price management routes
        Route::get('/offer-area-prices', [OfferAreaPriceController::class, 'index']);
        Route::post('/offer-area-prices', [OfferAreaPriceController::class, 'store']);
        Route::put('/offer-area-prices/{id}', [OfferAreaPriceController::class, 'update']);
        Route::delete('/offer-area-prices/{id}', [OfferAreaPriceController::class, 'destroy']);
        Route::get('/offer-area-prices/offer/{offerId}', [OfferAreaPriceController::class, 'getOfferPrices']);

        // Category 7: Duties - Admin routes
        // Nurse Visits
        Route::get('/nurse-visits', [\App\Http\Controllers\Admin\NurseVisitController::class, 'index']);
        Route::post('/nurse-visits', [\App\Http\Controllers\Admin\NurseVisitController::class, 'store']);
        Route::get('/nurse-visits/{nurseVisit}', [\App\Http\Controllers\Admin\NurseVisitController::class, 'show']);
        Route::match(['put', 'post'], '/nurse-visits/{nurseVisit}', [\App\Http\Controllers\Admin\NurseVisitController::class, 'update']);
        Route::delete('/nurse-visits/{nurseVisit}', [\App\Http\Controllers\Admin\NurseVisitController::class, 'destroy']);
        
        // Duties
        Route::get('/duties', [\App\Http\Controllers\Admin\DutyController::class, 'index']);
        Route::post('/duties', [\App\Http\Controllers\Admin\DutyController::class, 'store']);
        Route::get('/duties/{duty}', [\App\Http\Controllers\Admin\DutyController::class, 'show']);
        Route::match(['put', 'post'], '/duties/{duty}', [\App\Http\Controllers\Admin\DutyController::class, 'update']);
        Route::delete('/duties/{duty}', [\App\Http\Controllers\Admin\DutyController::class, 'destroy']);
        
        // Babysitters
        Route::get('/babysitters', [\App\Http\Controllers\Admin\BabysitterController::class, 'index']);
        Route::post('/babysitters', [\App\Http\Controllers\Admin\BabysitterController::class, 'store']);
        Route::get('/babysitters/{babysitter}', [\App\Http\Controllers\Admin\BabysitterController::class, 'show']);
        Route::match(['put', 'post'], '/babysitters/{babysitter}', [\App\Http\Controllers\Admin\BabysitterController::class, 'update']);
        Route::delete('/babysitters/{babysitter}', [\App\Http\Controllers\Admin\BabysitterController::class, 'destroy']);

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
    Route::post('/chat/threads/{threadId}/upload-url', [\App\Http\Controllers\ChatController::class, 'getUploadUrl']);
    Route::post('/chat/threads/{threadId}/messages', [\App\Http\Controllers\ChatController::class, 'postMessage']);
    Route::patch('/chat/threads/{threadId}/close', [\App\Http\Controllers\ChatController::class, 'close']);
});