<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class AdminCustomersController extends Controller
{
    /**
     * GET /api/admin/customers
     * List customers with optional search and pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Customer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%$search%")
                  ->orWhere('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ]
        ]);
    }

    /**
     * POST /api/admin/customers
     * Create a new customer with generated password
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'company_name' => 'nullable|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:customers,email',
                'phone' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                'shipping_address1' => 'nullable|string|max:255',
                'shipping_address2' => 'nullable|string|max:255',
                'shipping_city' => 'nullable|string|max:100',
                'shipping_state' => 'nullable|string|max:100',
                'shipping_zipcode' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
                'billing_address1' => 'nullable|string|max:255',
                'billing_address2' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_zipcode' => 'nullable|string|max:20',
                'billing_country' => 'nullable|string|max:100',
            ]);

            $randomPassword = Str::random(12);
            $hashedPassword = Hash::make($randomPassword);

            $customer = Customer::create(array_merge($validated, [
                'password' => $hashedPassword,
                'verification_key' => Str::random(32), // optional
            ]));

            return response()->json([
                'message' => 'Customer created successfully.',
                'customer' => $customer,
                // 'generated_password' => $randomPassword, // ⚠️ Debug only, remove in production
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Customer create error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/admin/customers/{id}
     * Show a single customer
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
     * PUT /api/admin/customers/{id}
     * Update a customer's data
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        try {
            $validated = $request->validate([
                'company_name' => 'nullable|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => "required|email|unique:customers,email,$id",
                'phone' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                'shipping_address1' => 'nullable|string|max:255',
                'shipping_address2' => 'nullable|string|max:255',
                'shipping_city' => 'nullable|string|max:100',
                'shipping_state' => 'nullable|string|max:100',
                'shipping_zipcode' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
                'billing_address1' => 'nullable|string|max:255',
                'billing_address2' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_zipcode' => 'nullable|string|max:20',
                'billing_country' => 'nullable|string|max:100',
            ]);

            $customer->update($validated);

            return response()->json([
                'message' => 'Customer updated successfully.',
                'customer' => $customer
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Customer update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/customers/{id}
     * Delete a customer
     */
    public function destroy(string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        try {
            $customer->delete();
            return response()->json(['message' => 'Customer deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Customer delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
