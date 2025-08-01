<?php

namespace App\Http\Controllers;

use App\Models\RmaRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RmaRequestController extends Controller
{
    /**
     * Store a new RMA request from a customer.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming data from the front-end form
        $request->validate([
            'productCode' => 'required|string',
            'serialNumber' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'invoiceDate' => 'required|date',
            'salesDocumentNo' => 'required|string',
            'returnReason' => 'required|string',
            'problemDescription' => 'required|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        // 2. Handle the photo upload
        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('rma_photos', 'public')
            : null;

        // 3. Create a new RMA request in the database
        $rma = RmaRequest::create([
            'customer_id' => $request->user()->id,
            'product_code' => $request->productCode,
            'description' => $request->description,
            'serial_number' => $request->serialNumber,
            'quantity' => $request->quantity,
            'invoice_date' => $request->invoiceDate,
            'sales_document_no' => $request->salesDocumentNo,
            'return_reason' => $request->returnReason,
            'problem_description' => $request->problemDescription,
            'photo_path' => $photoPath,
            'status' => 'Pending', // All new requests start with a 'Pending' status
        ]);

        // 4. Return a JSON response to the client
        return response()->json([
            'success' => true,
            'rmaNumber' => 'RMA-' . str_pad($rma->id, 6, '0', STR_PAD_LEFT),
            'productCode' => $rma->product_code,
            'serialNumber' => $rma->serial_number,
            'returnReason' => $rma->return_reason,
            'estimatedResolution' => '5–7 business days',
            'status' => $rma->status,
        ]);
    }

    /**
     * List all RMA requests (for admin) or just the customer's (for client).
     */
    public function index(Request $request)
    {
        $query = RmaRequest::query()->with(['customer', 'product']);

        // Check the user's role to determine what data to return
        if ($request->user() && $request->user()->isAdmin()) {
            // ADMIN VIEW: Return all RMAs with filtering
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('rma_number', 'like', "%$searchTerm%")
                      ->orWhereHas('customer', function ($subQuery) use ($searchTerm) {
                          $subQuery->where('name', 'like', "%$searchTerm%");
                      })
                      ->orWhereHas('product', function ($subQuery) use ($searchTerm) {
                          $subQuery->where('name', 'like', "%$searchTerm%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('returnReason')) {
                $query->where('return_reason', $request->input('returnReason'));
            }

            if ($request->filled('startDate') && $request->filled('endDate')) {
                $query->whereBetween('created_at', [$request->input('startDate'), $request->input('endDate')]);
            }

        } elseif ($request->user()) {
            // CLIENT VIEW: Return only the authenticated customer's RMAs
            $query->where('customer_id', $request->user()->id);
        } else {
            // Unauthorized or no user logged in
            return response()->json(['success' => true, 'data' => []]);
        }

        // Apply sorting and pagination to the final query
        $rmas = $query->latest()->paginate($request->input('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $rmas,
        ]);
    }

    /**
     * Display a single RMA request (for a detail view).
     */
    public function show(RmaRequest $rmaRequest)
    {
        // Use an authorization check to ensure only the customer or an admin can see this
        if (!auth()->user()->isAdmin() && auth()->id() !== $rmaRequest->customer_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Eager load relationships for the detail view
        $rmaRequest->load(['customer', 'product']);

        return response()->json([
            'success' => true,
            'data' => $rmaRequest,
        ]);
    }

    /**
     * Update the status of an RMA request (for admin).
     */
    public function update(Request $request, RmaRequest $rmaRequest)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:Pending,Approved,Rejected,Processing,Closed',
        ]);

        $rmaRequest->update([
            'status' => $request->input('status')
        ]);

        return response()->json([
            'success' => true,
            'data' => $rmaRequest,
            'message' => 'RMA status updated successfully.'
        ]);
    }
    
    /**
     * Get a list of all possible return reasons for a dropdown.
     */
    public function getReasons()
    {
        // This should be populated from your database or a config file
        $reasons = ['Defective', 'Wrong Item', 'Arrived Damaged', 'Did Not Fit', 'Other'];
        
        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }

    /**
     * Get a list of all possible statuses for a dropdown.
     */
    public function getStatuses()
    {
        $statuses = ['Pending', 'Approved', 'Rejected', 'Processing', 'Closed'];
        
        return response()->json([
            'success' => true,
            'data' => $statuses,
        ]);
    }
}