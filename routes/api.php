<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\RmaRequestController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AdminCustomersController;
use App\Http\Controllers\Admin\AdminAuthController; // Ensure this is imported

// ----------------------
// Public Routes
// ----------------------
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);

// ----------------------
// Admin Public Routes (e.g., login)
// ----------------------
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// ----------------------
// Customer Authenticated Routes
// ----------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/rma', [RmaRequestController::class, 'store']);
    Route::get('/rmas', [RmaRequestController::class, 'index']);
    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/profile', function (Request $request) {
        return response()->json([
            'message' => 'Welcome back!',
            'user' => $request->user(),
        ]);
    });
});
 Route::post('/logout', [CustomerAuthController::class, 'logout']);

// ----------------------
// Admin Authenticated Routes
// ----------------------
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // All routes within this group require a valid Sanctum API token.
    // You can add further authorization (roles/permissions) within your controllers
    // or by adding a custom middleware like 'ensure.admin' here if it checks roles.

    // Example with 'ensure.admin' if it's a separate role check:
    // Route::middleware('ensure.admin')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);

        Route::apiResource('customers', AdminCustomersController::class);
        Route::apiResource('products', ProductController::class);
       // Route::apiResource('rmas', RmaController::class);
        Route::get('/admin/rmas', [AdminRmaController::class, 'index']); // for fetchRmas
    Route::patch('/admin/rmas/{id}/status', [AdminRmaController::class, 'updateStatus']); // for updateRmaStatus
    Route::post('/admin/rmas/bulk-update-status', [AdminRmaController::class, 'bulkUpdateStatus']);
Route::get('/admin/rmas/export', [AdminRmaController::class, 'export']);

    // });
});
