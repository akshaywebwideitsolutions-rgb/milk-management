<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no', 'farmer_id', 'bill_cycle_id', 'period_start', 'period_end',
        'total_litres', 'gross_amount', 'total_deductions', 'net_amount',
        'status', 'sent_date', 'paid_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_litres' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'sent_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_no)) {
                $count = Invoice::count() + 1;
                $invoice->invoice_no = 'INV-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function billCycle()
    {
        return $this->belongsTo(BillCycle::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function collections()
    {
        return MilkCollection::where('farmer_id', $this->farmer_id)
            ->whereBetween('collection_date', [$this->period_start, $this->period_end])
            ->get();
    }

    public function deductions()
    {
        return $this->billCycle
            ? $this->billCycle->deductions()
                ->where('farmer_id', $this->farmer_id)
                ->where('status', 'applied')
                ->orderBy('deduction_date')
                ->get()
            : collect();
    }
}
