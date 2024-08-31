<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\ServiceController; // Import the ServiceController
use App\Http\Controllers\CategoryController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require auth and verified email)
Route::middleware(['auth:sanctum'])->group(function () {

    // Routes for all authenticated users
    Route::get('/users/{id}', [UserController::class, 'show']); // Get user details
    Route::put('/users/{id}', [UserController::class, 'update']); // Update user details
    
    // Routes accessible by both users and admins
    Route::get('/nurses', [NurseController::class, 'index']); // View all nurses
    Route::get('/nurses/{id}', [NurseController::class, 'show']); // View a specific nurse's details

    // Services accessible by both users and admins
    Route::get('/services', [ServiceController::class, 'index']); // List all services
    Route::get('/services/{service}', [ServiceController::class, 'show']); // View a specific service's details

         // Users and admins can view categories
         Route::get('/categories', [CategoryController::class, 'index']);
         Route::get('/categories/{id}', [CategoryController::class, 'show']);
    
    // Routes specific to "user" role
    Route::middleware('role:user')->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard']);
        Route::post('/submit-location', [UserController::class, 'submitLocationOnFirstLogin']);

        // Request management for users
        Route::post('/requests', [RequestController::class, 'store']); // Create a new request
        Route::get('/requests/{id}', [RequestController::class, 'show']); // Show a specific request

       


    });
    
    // Routes specific to "admin" role
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // User management routes
        Route::get('/users', [UserController::class, 'index']); // List all users
        Route::post('/users', [UserController::class, 'store']); // Create a new user
        Route::delete('/users/{id}', [UserController::class, 'destroy']); // Delete user

        // Nurse management routes
        Route::post('/nurses', [NurseController::class, 'store']); // Create a new nurse
        Route::put('/nurses/{id}', [NurseController::class, 'update']); // Update a nurse
        Route::delete('/nurses/{id}', [NurseController::class, 'destroy']); // Delete a nurse
        
        // Request management routes
        Route::get('/requests', [RequestController::class, 'index']); // List all requests
        Route::put('/requests/{id}', [RequestController::class, 'update']); // Update a request
        Route::delete('/requests/{id}', [RequestController::class, 'destroy']); // Delete a request

        // Service management routes
        Route::post('/services', [ServiceController::class, 'store']); // Create a new service
        Route::put('/services/{service}', [ServiceController::class, 'update']); // Update a service
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']); // Delete a service


        Route::post('/categories', [CategoryController::class, 'store']);    // Create a new category
        Route::put('/categories/{category}', [CategoryController::class, 'update']);  // Update a category
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']); // Delete a category
    });
});