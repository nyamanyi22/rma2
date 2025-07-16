<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    // Admin Login
public function login(Request $request)
{


    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation Failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $credentials = $request->only('email', 'password');
    $admin = Admin::where('email', $credentials['email'])->first();

    if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
        return response()->json(['message' => 'Invalid email or password'], 401);
    }

    $token = $admin->createToken('admin-token')->plainTextToken;


     return response()->json([
        'message' => 'Login successful',
        'admin' => $admin->makeHidden('password'),
        'token' => $token
    ]);
}


    // Logout (delete current token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    // Get currently logged-in admin
    public function me(Request $request)
    {
        return response()->json([
            'admin' => $request->user()
        ]);
    }
}
