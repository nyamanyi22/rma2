<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    /**
     * Handle customer registration.
     */
    public function register(Request $request)
    {
        // 1. Data Validation
        $validator = Validator::make($request->all(), [
            // Company info
            'company_name' => 'nullable|string|max:255',
            'is_not_company' => 'sometimes|boolean',
            'website' => 'nullable|url|max:255',

            // Personal info
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:8|confirmed',

            // Shipping Address
            'shipping_address1' => 'required|string|max:255',
            'shipping_address2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'required|string|max:255',
            'shipping_zipcode' => 'required|string|max:255',
            'shipping_country' => 'required|string|max:255',

            // Contact
            'phone' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',

            // Billing (using camelCase to match frontend)
            'isBillingAddressDifferent' => 'sometimes|boolean',
            'billingAddress1' => 'nullable|string|max:255',
            'billingAddress2' => 'nullable|string|max:255',
            'billingCity' => 'nullable|string|max:255',
            'billingState' => 'nullable|string|max:255',
            'billingZipcode' => 'nullable|string|max:255',
            'billingCountry' => 'nullable|string|max:255',

            // Optional
            'verification_key' => 'nullable|string|max:255',
        ]);

        // Conditional validation for billing address
        $validator->sometimes([
            'billingAddress1', 
            'billingCity',
            'billingState',
            'billingZipcode',
            'billingCountry'
        ], 'required', function ($input) {
            return $input->isBillingAddressDifferent;
        });

        // 2. Handle Validation Failure
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine if billing address is different
        $isBillingDifferent = $request->boolean('isBillingAddressDifferent') 
                           ?? $request->boolean('is_billing_address_different')
                           ?? false;

        // 3. Create New Customer with proper address handling
        $customer = Customer::create([
            'company_name' => $request->company_name ?? $request->companyName,
            'is_not_company' => $request->boolean('is_not_company') ?? $request->boolean('isNotCompany'),
            'website' => $request->website,
            'first_name' => $request->first_name ?? $request->firstName,
            'last_name' => $request->last_name ?? $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'fax' => $request->fax,
            
            // Shipping Address
            'shipping_address1' => $request->shipping_address1 ?? $request->shippingAddress1,
            'shipping_address2' => $request->shipping_address2 ?? $request->shippingAddress2,
            'shipping_city' => $request->shipping_city ?? $request->shippingCity,
            'shipping_state' => $request->shipping_state ?? $request->shippingState,
            'shipping_zipcode' => $request->shipping_zipcode ?? $request->shippingZipcode,
            'shipping_country' => $request->shipping_country ?? $request->shippingCountry,
            
            // Billing Address (copy from shipping if not different)
            'is_billing_address_different' => $isBillingDifferent,
            'billing_address1' => $isBillingDifferent 
                ? ($request->billingAddress1 ?? $request->billing_address1)
                : ($request->shipping_address1 ?? $request->shippingAddress1),
            'billing_address2' => $isBillingDifferent
                ? ($request->billingAddress2 ?? $request->billing_address2)
                : ($request->shipping_address2 ?? $request->shippingAddress2),
            'billing_city' => $isBillingDifferent
                ? ($request->billingCity ?? $request->billing_city)
                : ($request->shipping_city ?? $request->shippingCity),
            'billing_state' => $isBillingDifferent
                ? ($request->billingState ?? $request->billing_state)
                : ($request->shipping_state ?? $request->shippingState),
            'billing_zipcode' => $isBillingDifferent
                ? ($request->billingZipcode ?? $request->billing_zipcode)
                : ($request->shipping_zipcode ?? $request->shippingZipcode),
            'billing_country' => $isBillingDifferent
                ? ($request->billingCountry ?? $request->billing_country)
                : ($request->shipping_country ?? $request->shippingCountry),
            
            'verification_key' => $request->verification_key ?? $request->verificationKey,
        ]);

        // 4. Return Success Response
        return response()->json([
            'message' => 'Customer registered successfully!',
            'customer' => $customer->makeHidden('password')
        ], 201);
    }

    /**
     * Handle customer login.
     */
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
        $customer = Customer::where('email', $credentials['email'])->first();

        if (!$customer || !Hash::check($credentials['password'], $customer->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'customer' => $customer->makeHidden('password')
        ], 200);
    }
}