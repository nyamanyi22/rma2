<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\RmaRequestController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AdminCustomersController;
use App\Http\Controllers\Admin\AdminAuthController; 
use App\Http\Controllers\Admin\AdminRmaController;

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
            Route::get('/rmas', [AdminRmaController::class, 'index']);
    Route::get('/rmas/export', [AdminRmaController::class, 'export']);
    Route::get('/rmas/{id}', [AdminRmaController::class, 'show']);
    Route::put('/rmas/{id}/status', [AdminRmaController::class, 'updateStatus']);
    Route::post('/rmas/bulk-status-update', [AdminRmaController::class, 'bulkUpdateStatus']);
Route::put('/rmas/{id}', [AdminRmaController::class, 'update']);   // PUT /api/admin/rmas/{id}
Route::delete('/rmas/{id}', [AdminRmaController::class, 'destroy']); // DELETE /api/admin/rmas/{id}
    Route::middleware('auth:sanctum')->get('/rma-statuses', [AdminRmaController::class, 'getStatuses']);


    // });
});
