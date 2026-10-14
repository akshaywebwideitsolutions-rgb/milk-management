<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillCycle;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $cycleId = $request->query('bill_cycle_id');
        $currentCycle = BillCycle::where('status', 'active')->latest()->first();
        $query = Invoice::with(['farmer', 'billCycle']);

        if ($cycleId) {
            $query->where('bill_cycle_id', $cycleId);
        } elseif (! $request->has('bill_cycle_id') && $currentCycle) {
            $query->where('bill_cycle_id', $currentCycle->id);
        }

        $invoices = $query->orderByDesc('id')->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => [
                'invoices' => $invoices,
                'cycles' => BillCycle::orderByDesc('start_date')->get(),
                'current_cycle' => $currentCycle,
            ],
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate(['bill_cycle_id' => 'required|exists:bill_cycles,id']);

        $cycle = BillCycle::findOrFail($request->integer('bill_cycle_id'));
        $generated = app(InvoiceService::class)->generate($cycle);

        return response()->json(['message' => "{$generated} invoices generated.", 'data' => ['generated' => $generated]]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['farmer', 'billCycle']);

        return response()->json([
            'data' => [
                'invoice' => $invoice,
                'collections' => $invoice->collections(),
                'deductions' => $invoice->deductions(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validated = $request->validate(['status' => 'required|in:pending,sent,paid']);

        return DB::transaction(function () use ($invoice, $validated) {
            Farmer::withTrashed()->whereKey($invoice->farmer_id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($invoice->status, ['sent', 'paid'], true) && $validated['status'] === 'pending', 422, 'Finalized invoices cannot be reopened.');
            abort_if($invoice->status === 'paid' && $validated['status'] !== 'paid', 422, 'Paid invoices cannot be reopened.');
            if ($invoice->status === $validated['status']) {
                return response()->json(['message' => 'Status unchanged.', 'data' => $invoice]);
            }
            app(InvoiceService::class)->refresh($invoice);
            abort_if($invoice->net_amount <= 0, 422, 'Only positive invoices can be sent or paid.');
            $invoice->status = $validated['status'];
            if ($validated['status'] === 'sent') {
                $invoice->sent_date = now();
            }
            if ($validated['status'] === 'paid') {
                $invoice->paid_date = now();
            }
            $invoice->save();

            return response()->json(['message' => 'Invoice status updated.', 'data' => $invoice->refresh()]);
        });
    }
}
