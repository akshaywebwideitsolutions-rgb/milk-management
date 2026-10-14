<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    protected $fillable = [
        'farmer_id', 'bill_cycle_id', 'deduction_type', 'amount',
        'deduction_date', 'description', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deduction_date' => 'date',
        ];
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
}
