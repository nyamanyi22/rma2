<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // For generating UUIDs for account_no
use Illuminate\Http\JsonResponse; // Import JsonResponse

class AdminCustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get pagination and search parameters from the request
        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $search = $request->input('search');

        $query = Customer::query();

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%')
                  ->orWhere('account_no', 'like', '%' . $search . '%');
            });
        }

        // Fetch customers with pagination
        $customers = $query->paginate($perPage);

        // Return a JSON response with the paginated data
        return response()->json([
            'data' => $customers->items(), // The actual customer array for the current page
            'total' => $customers->total(), // Total number of customers across all pages
            'per_page' => $customers->perPage(), // Items per page
            'current_page' => $customers->currentPage(), // Current page number
            'last_page' => $customers->lastPage(), // Last page number
            'message' => 'Customers fetched successfully.'
        ], 200);
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Define validation rules based on the CreateCustomer.jsx formData and apiData
        $rules = [
            // Personal Information
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email', // Ensure email is unique in customers table
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|confirmed',
            'password_confirmation' => 'required|string|min:8', // Required for confirmation rule

            // Primary Contact (Phone & Fax)
            'phone' => 'required|string|min:7|max:20',
            'fax' => 'nullable|string|min:7|max:20',

            // Shipping Address
            'shipping_country' => 'required|string|max:255',
            'shipping_state' => 'required|string|max:255',
            'shipping_address1' => 'required|string|max:255',
            'shipping_address2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_zipcode' => 'required|string|max:20',

            // Billing Address (Conditional)
            'is_billing_address_different' => 'required|boolean',
            'billing_country' => 'nullable|string|max:255|required_if:is_billing_address_different,true',
            'billing_state' => 'nullable|string|max:255|required_if:is_billing_address_different,true',
            'billing_address1' => 'nullable|string|max:255|required_if:is_billing_address_different,true',
            'billing_address2' => 'nullable|string|max:255', // Optional even if different
            'billing_city' => 'nullable|string|max:255|required_if:is_billing_address_different,true',
            'billing_zipcode' => 'nullable|string|max:20|required_if:is_billing_address_different,true',
            // Removed billing_phone and billing_fax from validation as they are not in the frontend form

            // Optional Fields
            'verification_key' => 'nullable|string|max:255',
        ];

        // Custom error messages for clarity
        $messages = [
            'company_name.required_if' => 'Company name is required if not "Not a company".',
            'email.unique' => 'This email address is already registered.',
            'password.regex' => 'Password needs 8+ characters with at least one uppercase letter, one lowercase letter, and one number.',
            'password.confirmed' => 'The password confirmation does not match.',
            'phone.min' => 'Phone number must be at least 7 digits.',
            'fax.min' => 'Fax number must be at least 7 digits.',
            'required_if' => 'The :attribute field is required when billing address is different.',
        ];
        
// If billing is same as shipping, copy shipping fields to billing BEFORE validation
if (!$request->boolean('is_billing_address_different')) {
    $request->merge([
        'billing_country'   => $request->input('shipping_country'),
        'billing_state'     => $request->input('shipping_state'),
        'billing_address1'  => $request->input('shipping_address1'),
        'billing_address2'  => $request->input('shipping_address2'),
        'billing_city'      => $request->input('shipping_city'),
        'billing_zipcode'   => $request->input('shipping_zipcode'),
    ]);
}

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prepare data for customer creation
        $data = $request->only([
            'company_name', 'website', 'first_name', 'last_name', 'email',
            'phone', 'fax',
            'shipping_country', 'shipping_state', 'shipping_address1', 'shipping_address2',
            'shipping_city', 'shipping_zipcode',
            'is_billing_address_different',
            'billing_country', 'billing_state', 'billing_address1', 'billing_address2',
            'billing_city', 'billing_zipcode',
            'verification_key'
        ]);

        // Handle 'is_not_company' logic
        // If 'isNotCompany' checkbox was checked, company_name will be null from frontend.
        // Ensure backend treats it as null if the checkbox was true.
        // The frontend sends `company_name: isNotCompany ? null : formData.companyName`
        // So if company_name is null here, it means isNotCompany was true.
        if ($request->input('is_not_company')) { // Check the boolean flag sent from frontend
            $data['company_name'] = null;
            $data['website'] = null;
        }

        // Handle billing address logic
        if (!$data['is_billing_address_different']) {
            // If billing address is not different, copy shipping address fields
            $data['billing_country'] = $data['shipping_country'];
            $data['billing_state'] = $data['shipping_state'];
            $data['billing_address1'] = $data['shipping_address1'];
            $data['billing_address2'] = $data['shipping_address2']; // Can be null
            $data['billing_city'] = $data['shipping_city'];
            $data['billing_zipcode'] = $data['shipping_zipcode'];
            // Removed billing_phone and billing_fax from here as they are not in the frontend form
        }

        // Generate a unique account_no (e.g., a UUID or a custom sequence)
        // This is an admin-side creation, so we can auto-generate if not provided
        $data['account_no'] = (string) Str::uuid(); // Generate a UUID for account number

        // Hash the password
        $data['password'] = Hash::make($request->input('password'));

        try {
            $customer = Customer::create($data);

            return response()->json([
                'message' => 'Customer created successfully.',
                'customer' => $customer
            ], 201); // 201 Created status
        } catch (\Exception $e) {
            \Log::error('Error creating customer: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create customer.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        // This method would show details of a specific customer
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        return response()->json(['customer' => $customer], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
  
public function update(Request $request, $id)
{
    $customer = Customer::find($id);

    if (!$customer) {
        return response()->json(['message' => 'Customer not found'], 404);
    }

    // ✅ 1. Auto-fill billing address with shipping address if billing is not different
    if (!$request->boolean('is_billing_address_different')) {
        $request->merge([
            'billing_country'   => $request->input('shipping_country'),
            'billing_state'     => $request->input('shipping_state'),
            'billing_address1'  => $request->input('shipping_address1'),
            'billing_address2'  => $request->input('shipping_address2'),
            'billing_city'      => $request->input('shipping_city'),
            'billing_zipcode'   => $request->input('shipping_zipcode'),
        ]);
    }

    // ✅ 2. Validation rules
    $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
        'phone' => 'required|string|max:20',
        'company_name' => 'nullable|string|max:255',

        'shipping_country' => 'required|string|max:255',
        'shipping_state' => 'required|string|max:255',
        'shipping_address1' => 'required|string|max:255',
        'shipping_address2' => 'nullable|string|max:255',
        'shipping_city' => 'required|string|max:255',
        'shipping_zipcode' => 'required|string|max:20',

        // ✅ Conditionally required billing fields
        'billing_country' => 'required_if:is_billing_address_different,true|string|max:255',
        'billing_state' => 'required_if:is_billing_address_different,true|string|max:255',
        'billing_address1' => 'required_if:is_billing_address_different,true|string|max:255',
        'billing_address2' => 'nullable|string|max:255',
        'billing_city' => 'required_if:is_billing_address_different,true|string|max:255',
        'billing_zipcode' => 'required_if:is_billing_address_different,true|string|max:20',
    ];

    $messages = [
        'billing_country.required_if' => 'Billing country is required when billing address is different.',
        'billing_state.required_if' => 'Billing state is required when billing address is different.',
        'billing_address1.required_if' => 'Billing address is required when billing address is different.',
        'billing_city.required_if' => 'Billing city is required when billing address is different.',
        'billing_zipcode.required_if' => 'Billing ZIP code is required when billing address is different.',
    ];

    // ✅ 3. Run validation
    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // ✅ 4. Update customer fields
    $customer->update([
        'first_name' => $request->input('first_name'),
        'last_name' => $request->input('last_name'),
        'email' => $request->input('email'),
        'phone' => $request->input('phone'),
        'company_name' => $request->input('company_name'),

        'shipping_country' => $request->input('shipping_country'),
        'shipping_state' => $request->input('shipping_state'),
        'shipping_address1' => $request->input('shipping_address1'),
        'shipping_address2' => $request->input('shipping_address2'),
        'shipping_city' => $request->input('shipping_city'),
        'shipping_zipcode' => $request->input('shipping_zipcode'),

        'billing_country' => $request->input('billing_country'),
        'billing_state' => $request->input('billing_state'),
        'billing_address1' => $request->input('billing_address1'),
        'billing_address2' => $request->input('billing_address2'),
        'billing_city' => $request->input('billing_city'),
        'billing_zipcode' => $request->input('billing_zipcode'),

        'is_billing_address_different' => $request->boolean('is_billing_address_different'),
    ]);

    return response()->json(['message' => 'Customer updated successfully']);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        // This method would delete a customer
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        try {
            $customer->delete();
            return response()->json(['message' => 'Customer deleted successfully.'], 200);
        } catch (\Exception $e) {
            \Log::error('Error deleting customer: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete customer.', 'error' => $e->getMessage()], 500);
        }
    }
}
