<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;

// Public routes (no auth required)
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);

// Protected routes (auth via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json([
            'message' => 'Welcome back!',
            'user' => $request->user(),
        ]);
    });
    
    // Add more protected routes here
});
