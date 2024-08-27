<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login']); // Add login route

// Protected routes (require auth and verified email)
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::post('/submit-location', [UserController::class, 'submitLocationOnFirstLogin']);


});