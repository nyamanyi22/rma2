<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;

class AdminCustomerController extends Controller
{
     public function store(Request $request)
{
    $validated = $request->validate([
        'company_name' => 'nullable|string|max:255',
        'is_not_company' => 'nullable|boolean',
        'website' => 'nullable|url',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|string|min:7',
        'fax' => 'nullable|string',
        'shipping_address1' => 'required|string|max:255',
        'shipping_address2' => 'nullable|string|max:255',
        'shipping_city' => 'required|string|max:255',
        'shipping_state' => 'required|string|max:255',
        'shipping_zipcode' => 'required|string|max:20',
        'shipping_country' => 'required|string|max:255',
        'is_billing_address_different' => 'required|boolean',
        'billing_address1' => 'nullable|string|max:255',
        'billing_address2' => 'nullable|string|max:255',
        'billing_city' => 'nullable|string|max:255',
        'billing_state' => 'nullable|string|max:255',
        'billing_zipcode' => 'nullable|string|max:20',
        'billing_country' => 'nullable|string|max:255',
        'verification_key' => 'nullable|string|max:255',
    ]);

    try {
        // Create the user with a dummy password
        $user = User::create([
            'company_name' => $validated['company_name'],
            'is_not_company' => $validated['is_not_company'] ?? false,
            'website' => $validated['website'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'fax' => $validated['fax'],
            'shipping_address1' => $validated['shipping_address1'],
            'shipping_address2' => $validated['shipping_address2'],
            'shipping_city' => $validated['shipping_city'],
            'shipping_state' => $validated['shipping_state'],
            'shipping_zipcode' => $validated['shipping_zipcode'],
            'shipping_country' => $validated['shipping_country'],
            'is_billing_address_different' => $validated['is_billing_address_different'],
            'billing_address1' => $validated['billing_address1'],
            'billing_address2' => $validated['billing_address2'],
            'billing_city' => $validated['billing_city'],
            'billing_state' => $validated['billing_state'],
            'billing_zipcode' => $validated['billing_zipcode'],
            'billing_country' => $validated['billing_country'],
            'verification_key' => $validated['verification_key'],
            'password' => Hash::make('temporary1234'),
        ]);

        // Optional: assign a role
        // $user->assignRole('customer');

        // Send reset link
        Password::sendResetLink(['email' => $user->email]);

        return response()->json([
            'message' => 'Customer created and password setup email sent.',
            'user' => $user,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to create customer.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
