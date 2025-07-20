<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer; // Make sure your Customer model is imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // For generating random strings
use Illuminate\Validation\ValidationException;

class AdminCustomersController extends Controller
{
    /**
     * Display a listing of the customers.
     * Supports search and pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get pagination and search parameters from the request
        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $search = $request->input('search');

        $customers = Customer::query();

        // Apply search filter if provided
        if ($search) {
            $customers->where(function ($query) use ($search) {
                $query->where('company_name', 'like', '%' . $search . '%')
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        // Paginate the results
        $paginatedCustomers = $customers->paginate($perPage);

        // Return a structured JSON response
        return response()->json([
            'data' => $paginatedCustomers->items(), // The actual customer data for the current page
            'meta' => [
                'total' => $paginatedCustomers->total(),
                'per_page' => $paginatedCustomers->perPage(),
                'current_page' => $paginatedCustomers->currentPage(),
                'last_page' => $paginatedCustomers->lastPage(),
                'from' => $paginatedCustomers->firstItem(),
                'to' => $paginatedCustomers->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created customer in storage.
     * Generates a random password for the admin-created customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'company_name' => 'nullable|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:customers,email', // Email must be unique
                'phone' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                'shipping_address1' => 'nullable|string|max:255',
                'shipping_address2' => 'nullable|string|max:255',
                'shipping_city' => 'nullable|string|max:100',
                'shipping_state' => 'nullable|string|max:100',
                'shipping_zipcode' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
            ]);

            // Generate a random password for admin-created customers
            // Customers can then use a "forgot password" flow or be sent this password.
            $randomPassword = Str::random(12); // Generates a 12-character random string
            $hashedPassword = Hash::make($randomPassword);

            $customer = Customer::create(array_merge($validatedData, [
                'password' => $hashedPassword,
                // You might add a 'status' or 'is_active' field here
                // 'status' => 'pending_activation',
            ]));

            // Optional: You could dispatch an event here to send an email
            // to the customer with instructions to set their password.

            return response()->json([
                'message' => 'Customer created successfully!',
                'customer' => $customer,
                // For debugging, you might temporarily return the plain password,
                // but NEVER do this in production.
                // 'generated_password' => $randomPassword,
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            \Log::error('Error creating customer: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'An unexpected error occurred while creating the customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified customer.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        return response()->json($customer);
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        try {
            $validatedData = $request->validate([
                'company_name' => 'nullable|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:customers,email,' . $id, // Email must be unique, except for current customer
                'phone' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                'shipping_address1' => 'nullable|string|max:255',
                'shipping_address2' => 'nullable|string|max:255',
                'shipping_city' => 'nullable|string|max:100',
                'shipping_state' => 'nullable|string|max:100',
                'shipping_zipcode' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
                // Password should typically not be updated via this form.
                // If needed, add 'password' => 'nullable|string|min:8|confirmed'
            ]);

            $customer->update($validatedData);

            return response()->json([
                'message' => 'Customer updated successfully!',
                'customer' => $customer,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating customer: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'An unexpected error occurred while updating the customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        try {
            $customer->delete();
            return response()->json(['message' => 'Customer deleted successfully!'], 200);
        } catch (\Exception $e) {
            \Log::error('Error deleting customer: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'An unexpected error occurred while deleting the customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}