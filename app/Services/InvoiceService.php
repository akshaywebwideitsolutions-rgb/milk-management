<?php

namespace App\Services;

use App\Models\BillCycle;
use App\Models\Farmer;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function refresh(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            Farmer::withTrashed()->whereKey($invoice->farmer_id)->lockForUpdate()->firstOrFail();
            $invoice->refresh();
            if (in_array($invoice->status, ['sent', 'paid'], true)) {
                return $invoice;
            }
            $collections = $invoice->collections();
            $deductions = $invoice->deductions()->sum('amount');
            $net = $collections->sum('amount') - $deductions;
            $invoice->forceFill([
                'total_litres' => $collections->sum('quantity'),
                'gross_amount' => $collections->sum('amount'),
                'total_deductions' => $deductions,
                'net_amount' => $net,
                'status' => $net > 0 ? 'pending' : 'draft',
            ])->save();

            return $invoice;
        });
    }

    public function generate(BillCycle $cycle): int
    {
        return DB::transaction(function () use ($cycle) {
            $cycle = BillCycle::whereKey($cycle->id)->lockForUpdate()->firstOrFail();
            $farmers = Farmer::withTrashed()->where(function ($q) use ($cycle) {
                $q->whereHas('collections', fn ($q) => $q->whereBetween('collection_date', [$cycle->start_date, $cycle->end_date]))
                    ->orWhereHas('deductions', fn ($q) => $q->where('bill_cycle_id', $cycle->id)->where('status', 'applied'));
            })->get();
            $generated = 0;
            foreach ($farmers as $farmer) {
                Farmer::withTrashed()->whereKey($farmer->id)->lockForUpdate()->firstOrFail();
                $invoice = Invoice::firstOrNew(['farmer_id' => $farmer->id, 'bill_cycle_id' => $cycle->id]);
                if (! $invoice->exists) {
                    $invoice->fill(['period_start' => $cycle->start_date, 'period_end' => $cycle->end_date, 'status' => 'draft', 'created_by' => auth()->id()])->save();
                    $generated++;
                }
                $this->refresh($invoice);
            }

            return $generated;
        });
    }
}
