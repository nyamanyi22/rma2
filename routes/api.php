<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\RmaRequestController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\Admin\AdminAuthController;

// ----------------------
// Public Routes
// ----------------------
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);
Route::post('/admin/login', [AdminAuthController::class, 'login']); // Admin login

// ----------------------
// Customer Authenticated Routes
// ----------------------
Route::middleware('auth:sanctum')->group(function () {
    // RMA Requests
    Route::post('/rma', [RmaRequestController::class, 'store']);
    Route::get('/rmas', [RmaRequestController::class, 'index']);

    // Authenticated user info
    Route::get('/user', fn(Request $request) => $request->user());

    // Simple test/profile endpoint
    Route::get('/profile', function (Request $request) {
        return response()->json([
            'message' => 'Welcome back!',
            'user' => $request->user(),
        ]);
    });
});

// ----------------------
// Admin Authenticated Routes
// ----------------------
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {

    // Authenticated admin user
    Route::get('/me', [AdminAuthController::class, 'me']);

    // Create customer (admin)
    Route::post('/customers', [AdminCustomerController::class, 'store']);

    // Product management
    Route::apiResource('products', ProductController::class);
});
