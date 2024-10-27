<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-time', function () {
    return response()->json([
        'current_time' => now()->toDateTimeString(),
        'timezone' => config('app.timezone'),
    ]);
});