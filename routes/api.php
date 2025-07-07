<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\RmaRequestController;

use App\Http\Controllers\AdminCustomerController;// admin api
// Admin routes
Route::middleware('auth:sanctum')->post('/admin/customers', [AdminCustomerController::class, 'store']);

// Public routes (no auth required)
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);

// Protected routes (auth via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // RMA Routes
    Route::post('/rma', [RmaRequestController::class, 'store']);
    Route::get('/rmas', [RmaRequestController::class, 'index']);

    // User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/profile', function (Request $request) {
        return response()->json([
            'message' => 'Welcome back!',
            'user' => $request->user(),
        ]);
    });

    // Add more protected routes here...
});
