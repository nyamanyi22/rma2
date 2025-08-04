<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RmaRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Response;

class AdminRmaController extends Controller
{
    public function index(Request $request)
    {
        $query = RmaRequest::query();

        if ($request->filled('search')) {
            $query->where('reference_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->startDate);
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->endDate);
        }

        if ($request->filled('returnReason')) {
            $query->where('return_reason', $request->returnReason);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    public function show($id)
    {
        $rma = RmaRequest::with('customer')->findOrFail($id);
        return response()->json($rma);
    }

    public function updateStatus($id, Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'Approved', 'Rejected', 'Processing', 'Closed'])]
        ]);

        $rma = RmaRequest::findOrFail($id);
        $rma->status = $validated['status'];
        $rma->save();

        return response()->json(['message' => 'RMA status updated successfully.']);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string|in:Pending,Approved,Rejected,Processing,Completed',
        ]);

        $updated = RmaRequest::whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'message' => "Updated $updated RMA(s) successfully.",
        ]);
    }

    public function export(Request $request)
    {
        $query = RmaRequest::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('customer', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
                  ->orWhere('reference_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->startDate);
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->endDate);
        }

        $rmas = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="rmas_export.csv"',
        ];

        $callback = function () use ($rmas) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Customer', 'Reference No', 'Status', 'Reason', 'Created At']);

            foreach ($rmas as $rma) {
                fputcsv($handle, [
                    $rma->id,
                    $rma->customer->name ?? 'N/A',
                    $rma->reference_number,
                    $rma->status,
                    $rma->return_reason,
                    $rma->created_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
