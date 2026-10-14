<?php

namespace App\Http\Controllers;

use App\Models\BillCycle;
use App\Models\Deduction;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $cycleId = $request->get('bill_cycle_id');
        $currentCycle = BillCycle::where('status', 'active')->latest()->first();
        $cycles = BillCycle::orderBy('start_date', 'desc')->get();

        $query = Invoice::with(['farmer', 'billCycle']);
        if ($cycleId) {
            $query->where('bill_cycle_id', $cycleId);
        } elseif ($currentCycle) {
            $query->where('bill_cycle_id', $currentCycle->id);
        }

        $invoices = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('invoices.index', compact('invoices', 'cycles', 'currentCycle'));
    }

    public function generate(Request $request)
    {
        $cycleId = $request->get('bill_cycle_id');
        $cycle = BillCycle::findOrFail($cycleId);

        $farmers = Farmer::where('status', 'active')->get();
        $generated = 0;

        DB::transaction(function () use ($cycle, $farmers, &$generated) {
            foreach ($farmers as $farmer) {
                // Skip if invoice already exists for this farmer + cycle
                if (Invoice::where('farmer_id', $farmer->id)->where('bill_cycle_id', $cycle->id)->exists()) {
                    continue;
                }

                $collections = MilkCollection::where('farmer_id', $farmer->id)
                    ->whereBetween('collection_date', [$cycle->start_date, $cycle->end_date])
                    ->get();

                $totalLitres = $collections->sum('quantity');
                $grossAmount = $collections->sum('amount');
                $totalDeductions = Deduction::where('farmer_id', $farmer->id)
                    ->where('bill_cycle_id', $cycle->id)
                    ->where('status', 'applied')
                    ->sum('amount');
                $netAmount = $grossAmount - $totalDeductions;

                Invoice::create([
                    'farmer_id'        => $farmer->id,
                    'bill_cycle_id'    => $cycle->id,
                    'period_start'     => $cycle->start_date,
                    'period_end'       => $cycle->end_date,
                    'total_litres'     => $totalLitres,
                    'gross_amount'     => $grossAmount,
                    'total_deductions' => $totalDeductions,
                    'net_amount'       => $netAmount,
                    'status'           => $netAmount > 0 ? 'pending' : 'draft',
                    'created_by'       => auth()->id(),
                ]);

                $generated++;
            }
        });

        return redirect()->back()->with('success', "{$generated} invoices generated.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['farmer', 'billCycle']);
        $collections = $invoice->collections();
        $deductions = $invoice->deductions();

        return view('invoices.show', compact('invoice', 'collections', 'deductions'));
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate(['status' => 'required|in:pending,sent,paid']);
        $invoice->status = $request->status;
        if ($request->status === 'sent') $invoice->sent_date = now();
        if ($request->status === 'paid')  $invoice->paid_date = now();
        $invoice->save();

        return redirect()->back()->with('success', 'Invoice status updated.');
    }

    public function printInvoice(Invoice $invoice)
    {
        $invoice->load(['farmer', 'billCycle']);
        $collections = $invoice->collections();
        $deductions = $invoice->deductions();

        $pdf = Pdf::loadView('invoices.print', compact('invoice', 'collections', 'deductions'));
        return $pdf->stream("{$invoice->invoice_no}.pdf");
    }

    public function printAll(Request $request)
    {
        $cycleId = $request->get('bill_cycle_id');
        $invoices = Invoice::with(['farmer', 'billCycle'])
            ->where('bill_cycle_id', $cycleId)
            ->whereNot('status', 'draft')
            ->get();

        $pdf = Pdf::loadView('invoices.print-all', compact('invoices'));
        return $pdf->stream('all-invoices.pdf');
    }
}
