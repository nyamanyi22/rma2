<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RmaRequest;
use App\Enums\RmaStatus;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Builder;

class AdminRmaController extends Controller
{
    private array $validStatuses;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->validStatuses = RmaStatus::values();
    }

    public function index(Request $request)
    {
        $query = $this->applyFilters(RmaRequest::with('customer'), $request);
        $perPage = $request->input('limit', 20);
        $rmas = $query->latest()->paginate($perPage);

        return response()->json($rmas);
    }

    public function show($id)
    {
        $rma = RmaRequest::with('customer')->findOrFail($id);
        return response()->json($rma);
    }

    public function updateStatus($id, Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in($this->validStatuses)],
        ]);

        $rma = RmaRequest::findOrFail($id);
        $rma->status = $validated['status'];
        $rma->save();

        return response()->json(['message' => 'RMA status updated successfully.']);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'status' => ['required', Rule::in($this->validStatuses)],
        ]);

        $updated = RmaRequest::whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'message' => "Updated $updated RMA(s) successfully.",
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->applyFilters(RmaRequest::with('customer'), $request);
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

    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn ($q) => $q->where('name', 'like', "%$search%"))
                  ->orWhere('reference_number', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->input('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->input('endDate'));
        }

        if ($request->filled('returnReason')) {
            $query->where('return_reason', $request->input('returnReason'));
        }

        return $query;
    }
}
