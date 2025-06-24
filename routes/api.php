<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;

Route::middleware('api')->group(function () {
    Route::post('/register', [CustomerAuthController::class, 'register']);
});
Route::post('/login', [CustomerAuthController::class, 'login']);
