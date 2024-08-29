<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RequestController; // Import new controllers

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require auth and verified email)
Route::middleware(['auth:sanctum'])->group(function () {

    // Routes for all authenticated users
 
    Route::get('/users/{id}', [UserController::class, 'show']); // Get user details
    Route::put('/users/{id}', [UserController::class, 'update']); // Update user details
    Route::delete('/users/{id}', [UserController::class, 'destroy']); // Delete user (Admin only)

    // Routes specific to "user" role
    Route::middleware('role:user')->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard']);
        Route::post('/submit-location', [UserController::class, 'submitLocationOnFirstLogin']);
    });

    // Routes specific to "admin" role
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index']); // List all users
        Route::post('/admin/users', [UserController::class, 'store']); // Create a new user
       
       
       
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::post('/admin/nurses', [AdminController::class, 'addNurse']);
        Route::put('/admin/nurses/{id}', [AdminController::class, 'updateNurse']);
        Route::delete('/admin/nurses/{id}', [AdminController::class, 'deleteNurse']);
        Route::get('/admin/requests', [AdminController::class, 'viewRequests']);
        // Add other admin-specific routes here
    });
});