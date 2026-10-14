<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillCycle;
use App\Models\Deduction;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($data) {
            Farmer::withTrashed()->whereKey($data['farmer_id'])->lockForUpdate()->firstOrFail();
            $this->assertEditable($data['farmer_id'], $data['bill_cycle_id']);
            $cycle = BillCycle::findOrFail($data['bill_cycle_id']);
            abort_unless(Carbon::parse($data['deduction_date'])->betweenIncluded($cycle->start_date, $cycle->end_date), 422, 'Deduction date must be within the selected cycle.');
            $data['status'] = 'applied';
            $data['created_by'] = auth()->id();
            $deduction = Deduction::create($data);
            $this->refreshInvoiceTotals($deduction->farmer_id, $deduction->bill_cycle_id);

            return response()->json(['message' => 'Deduction added.', 'data' => $deduction], 201);
        });
    }

    public function update(Request $request, Deduction $deduction)
    {
        return DB::transaction(function () use ($request, $deduction) {
            Farmer::withTrashed()->whereKey($deduction->farmer_id)->lockForUpdate()->firstOrFail();
            $deduction = Deduction::whereKey($deduction->id)->lockForUpdate()->firstOrFail();
            $this->assertEditable($deduction->farmer_id, $deduction->bill_cycle_id);
            $oldFarmerId = $deduction->farmer_id;
            $oldCycleId = $deduction->bill_cycle_id;

            $cycle = BillCycle::findOrFail($deduction->bill_cycle_id);
            $validated = $request->validate([
                'deduction_type' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0',
                'deduction_date' => 'required|date',
                'status' => 'required|in:pending,applied',
                'description' => 'nullable|string|max:300',
            ]);
            abort_unless(Carbon::parse($validated['deduction_date'])->betweenIncluded($cycle->start_date, $cycle->end_date), 422, 'Deduction date must be within the selected cycle.');
            $deduction->update($validated);
            $this->refreshInvoiceTotals($oldFarmerId, $oldCycleId);
            $this->refreshInvoiceTotals($deduction->farmer_id, $deduction->bill_cycle_id);

            return response()->json(['message' => 'Deduction updated.', 'data' => $deduction->refresh()]);
        });
    }

    public function destroy(Deduction $deduction)
    {
        return DB::transaction(function () use ($deduction) {
            Farmer::withTrashed()->whereKey($deduction->farmer_id)->lockForUpdate()->firstOrFail();
            $deduction = Deduction::whereKey($deduction->id)->lockForUpdate()->firstOrFail();
            $this->assertEditable($deduction->farmer_id, $deduction->bill_cycle_id);
            $farmerId = $deduction->farmer_id;
            $cycleId = $deduction->bill_cycle_id;
            $deduction->delete();
            $this->refreshInvoiceTotals($farmerId, $cycleId);

            return response()->json(['message' => 'Deduction deleted.']);
        });
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
        if (! $farmerId || ! $cycleId) {
            return;
        }

        $invoice = Invoice::where('farmer_id', $farmerId)
            ->where('bill_cycle_id', $cycleId)
            ->first();

        if (! $invoice) {
            return;
        }

        app(InvoiceService::class)->refresh($invoice);
    }

    private function assertEditable(int $farmerId, int $cycleId): void
    {
        abort_if(Invoice::where('farmer_id', $farmerId)->where('bill_cycle_id', $cycleId)->whereIn('status', ['sent', 'paid'])->exists(), 422, 'Deductions for finalized invoices cannot be changed.');
    }
}
