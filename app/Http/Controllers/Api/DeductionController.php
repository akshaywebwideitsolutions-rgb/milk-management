<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillCycle;
use App\Models\Deduction;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    public function index(Request $request)
    {
        $cycleId = $request->query('bill_cycle_id');
        $query = Deduction::with(['farmer', 'billCycle', 'createdBy']);

        if ($cycleId) {
            $query->where('bill_cycle_id', $cycleId);
        }

        return response()->json([
            'data' => [
                'deductions' => $query->orderByDesc('deduction_date')->paginate($request->integer('per_page', 20)),
                'cycles' => BillCycle::orderByDesc('start_date')->get(),
                'farmers' => Farmer::where('status', 'active')->orderBy('name')->get(),
                'total_this_cycle' => (float) Deduction::when($cycleId, fn ($q) => $q->where('bill_cycle_id', $cycleId))
                    ->where('status', 'applied')
                    ->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['status'] = 'applied';
        $data['created_by'] = auth()->id();
        $deduction = Deduction::create($data);
        $this->refreshInvoiceTotals($deduction->farmer_id, $deduction->bill_cycle_id);

        return response()->json(['message' => 'Deduction added.', 'data' => $deduction], 201);
    }

    public function update(Request $request, Deduction $deduction)
    {
        $oldFarmerId = $deduction->farmer_id;
        $oldCycleId = $deduction->bill_cycle_id;

        $deduction->update($request->validate([
            'deduction_type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'deduction_date' => 'required|date',
            'status' => 'required|in:pending,applied',
            'description' => 'nullable|string|max:300',
        ]));
        $this->refreshInvoiceTotals($oldFarmerId, $oldCycleId);
        $this->refreshInvoiceTotals($deduction->farmer_id, $deduction->bill_cycle_id);

        return response()->json(['message' => 'Deduction updated.', 'data' => $deduction->refresh()]);
    }

    public function destroy(Deduction $deduction)
    {
        $farmerId = $deduction->farmer_id;
        $cycleId = $deduction->bill_cycle_id;
        $deduction->delete();
        $this->refreshInvoiceTotals($farmerId, $cycleId);

        return response()->json(['message' => 'Deduction deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'bill_cycle_id' => 'required|exists:bill_cycles,id',
            'deduction_type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'deduction_date' => 'required|date',
            'description' => 'nullable|string|max:300',
        ]);
    }

    private function refreshInvoiceTotals(?int $farmerId, ?int $cycleId): void
    {
        if (!$farmerId || !$cycleId) {
            return;
        }

        $invoice = Invoice::where('farmer_id', $farmerId)
            ->where('bill_cycle_id', $cycleId)
            ->first();

        if (!$invoice) {
            return;
        }

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
    }
}
