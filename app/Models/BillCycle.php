<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillCycle extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'cycle_type',
        'auto_generate_invoice', 'sms_on_invoice', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'auto_generate_invoice' => 'boolean',
            'sms_on_invoice' => 'boolean',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }
}
