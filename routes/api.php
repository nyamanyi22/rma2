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
Route::post('/admin/login', [AdminAuthController::class, 'login']); // ✅ Admin Login

// ----------------------
// Customer Auth Routes
// ----------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/rma', [RmaRequestController::class, 'store']);
    Route::get('/rmas', [RmaRequestController::class, 'index']);

    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/profile', fn(Request $request) => response()->json([
        'message' => 'Welcome back!',
        'user' => $request->user(),
    ]));
});

// ----------------------
// Admin Auth Routes
// ----------------------
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::post('/customers', [AdminCustomerController::class, 'store']);
    Route::apiResource('products', ProductController::class);
});

