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
        $this->validStatuses = array_column(RmaStatus::cases(), 'value');
    }

    /**
     * GET /api/admin/rmas
     * Fetch paginated, filtered list of RMA requests.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 20);

        $query = RmaRequest::with('customer', 'product')->latest();

        // Apply filters
        $this->applyFilters($query, $request);

        $rmas = $query->paginate($limit);

        return response()->json($rmas);
    }

    /**
     * PATCH /api/admin/rmas/{id}/status
     * Update single RMA status.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in($this->validStatuses)],
        ]);

        $rma = RmaRequest::findOrFail($id);
        $rma->status = $validated['status'];
        $rma->save();

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * POST /api/admin/rmas/bulk-update-status
     * Bulk update multiple RMAs' statuses.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:rma_requests,id',
            'status' => ['required', Rule::in($this->validStatuses)],
        ]);

        RmaRequest::whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Statuses updated successfully']);
    }

    /**
     * GET /api/admin/rmas/export
     * Export filtered RMAs as CSV
     */
    public function export(Request $request)
    {
        $query = RmaRequest::with('customer')->latest();
        $this->applyFilters($query, $request);
        $rmas = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="rmas_export.csv"',
        ];

        $callback = function () use ($rmas) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'RMA Number', 'Customer', 'Status', 'Return Reason', 'Created At']);

            foreach ($rmas as $rma) {
                fputcsv($handle, [
                    $rma->id,
                    $rma->rma_number,
                    $rma->customer->name ?? 'N/A',
                    $rma->status,
                    $rma->return_reason,
                    $rma->created_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Private: Applies filters to the query.
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('customer', fn($sub) => $sub->where('name', 'like', "%$search%"))
                       ->orWhere('rma_number', 'like', "%$search%");
                });
            })
            ->when(
                $request->filled('status') && in_array($request->status, $this->validStatuses),
                fn($q) => $q->where('status', $request->status)
            )
            ->when($request->filled('startDate'), fn($q) => $q->whereDate('created_at', '>=', $request->startDate))
            ->when($request->filled('endDate'), fn($q) => $q->whereDate('created_at', '<=', $request->endDate))
            ->when($request->filled('returnReason'), fn($q) => $q->where('return_reason', $request->returnReason));
    }
    public function destroy($id)
{
    $rma = RmaRequest::find($id);

    if (!$rma) {
        return response()->json(['message' => 'RMA not found.'], 404);
    }

    $rma->delete();

    return response()->json(['message' => 'RMA deleted successfully.']);
}
public function update(Request $request, $id)
{
    $rma = RmaRequest::find($id);

    if (!$rma) {
        return response()->json(['message' => 'RMA not found.'], 404);
    }

 $validated = $request->validate([
    'customer_id' => 'exists:customers,id',
    'product_code' => 'string|required',
    'description' => 'string|nullable',
    'serial_number' => 'string|required',
    'quantity' => 'integer|min:1|required',
    'invoice_date' => 'date|required',
    'sales_document_no' => 'string|required',
    'return_reason' => 'string|required',
    'problem_description' => 'string|required',
    'photo_path' => 'string|nullable',
    'status' => ['string', Rule::in(RmaStatus::values())],
]);


    $rma->update($validated);

    return response()->json([
        'message' => 'RMA updated successfully.',
        'rma' => $rma,
    ]);
}
public function show($id)
{
    $rma = RmaRequest::with('customer')->find($id);

    if (!$rma) {
        return response()->json(['message' => 'RMA not found.'], 404);
    }

    return response()->json([
        'rma' => $rma,
    ]);
}

public function getStatuses()
{
    $statuses = array_map(function($value) {
        return [
            'value' => $value,
            'label' => RmaStatus::labels()[$value] ?? $value,
        ];
    }, RmaStatus::values());

    return response()->json([
        'statuses' => $statuses
    ]);
}

}
