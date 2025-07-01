<?php

namespace App\Http\Controllers;

use App\Models\RmaRequest;
use Illuminate\Http\Request;

class RmaRequestController extends Controller
{
    /**
     * Store a new RMA request.
     */
    public function store(Request $request)
{
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

    $photoPath = $request->hasFile('photo')
        ? $request->file('photo')->store('rma_photos', 'public')
        : null;

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
        'status' => 'Pending', // Optional: set default status manually
    ]);

    return response()->json([
        'success' => true,
        'rmaNumber' => 'RMA-' . str_pad($rma->id, 6, '0', STR_PAD_LEFT),
        'productCode' => $rma->product_code,
        'serialNumber' => $rma->serial_number,
        'returnReason' => $rma->return_reason,
        'estimatedResolution' => '5–7 business days',
        'status' => $rma->status, // ✅ Add this line
    ]);
}


    /**
     * List all RMA requests for the logged-in customer.
     */
    public function index(Request $request)
    {
        try {
            $rmas = RmaRequest::where('customer_id', $request->user()->id)->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $rmas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not fetch RMA requests: ' . $e->getMessage()
            ], 500);
        }
    }
}
