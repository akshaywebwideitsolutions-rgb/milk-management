<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillCycle;
use App\Models\Deduction;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
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
        } elseif ($currentCycle) {
            $query->where('bill_cycle_id', $currentCycle->id);
        }

        $invoices = $query->orderByDesc('id')->paginate($request->integer('per_page', 20));
        $invoices->getCollection()->each(function (Invoice $invoice) {
            $this->refreshInvoiceTotals($invoice);
        });

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
        $farmers = Farmer::where('status', 'active')->get();
        $generated = 0;

        DB::transaction(function () use ($cycle, $farmers, &$generated) {
            foreach ($farmers as $farmer) {
                $existingInvoice = Invoice::where('farmer_id', $farmer->id)
                    ->where('bill_cycle_id', $cycle->id)
                    ->first();

                if ($existingInvoice) {
                    $this->refreshInvoiceTotals($existingInvoice);
                    continue;
                }

                $collections = MilkCollection::where('farmer_id', $farmer->id)
                    ->whereBetween('collection_date', [$cycle->start_date, $cycle->end_date])
                    ->get();

                $grossAmount = $collections->sum('amount');
                $totalDeductions = Deduction::where('farmer_id', $farmer->id)
                    ->where('bill_cycle_id', $cycle->id)
                    ->where('status', 'applied')
                    ->sum('amount');
                $netAmount = $grossAmount - $totalDeductions;

                Invoice::create([
                    'farmer_id' => $farmer->id,
                    'bill_cycle_id' => $cycle->id,
                    'period_start' => $cycle->start_date,
                    'period_end' => $cycle->end_date,
                    'total_litres' => $collections->sum('quantity'),
                    'gross_amount' => $grossAmount,
                    'total_deductions' => $totalDeductions,
                    'net_amount' => $netAmount,
                    'status' => $netAmount > 0 ? 'pending' : 'draft',
                    'created_by' => auth()->id(),
                ]);

                $generated++;
            }
        });

        return response()->json(['message' => "{$generated} invoices generated.", 'data' => ['generated' => $generated]]);
    }

    public function show(Invoice $invoice)
    {
        $this->refreshInvoiceTotals($invoice);
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

        $invoice->status = $validated['status'];
        if ($validated['status'] === 'sent') {
            $invoice->sent_date = now();
        }
        if ($validated['status'] === 'paid') {
            $invoice->paid_date = now();
        }
        $invoice->save();

        return response()->json(['message' => 'Invoice status updated.', 'data' => $invoice->refresh()]);
    }

    private function refreshInvoiceTotals(Invoice $invoice): Invoice
    {
        $collections = MilkCollection::where('farmer_id', $invoice->farmer_id)
            ->whereBetween('collection_date', [$invoice->period_start, $invoice->period_end])
            ->get();

        $grossAmount = $collections->sum('amount');
        $totalDeductions = Deduction::where('farmer_id', $invoice->farmer_id)
            ->where('bill_cycle_id', $invoice->bill_cycle_id)
            ->where('status', 'applied')
            ->sum('amount');

        $invoice->forceFill([
            'total_litres' => $collections->sum('quantity'),
            'gross_amount' => $grossAmount,
            'total_deductions' => $totalDeductions,
            'net_amount' => $grossAmount - $totalDeductions,
        ])->save();

        return $invoice->refresh()->load(['farmer', 'billCycle']);
    }
}
