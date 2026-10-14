<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilkCollection extends Model
{
    protected $fillable = [
        'farmer_id', 'collection_date', 'session', 'milk_type', 'request_id',
        'fat_percent', 'snf_percent', 'quantity', 'rate_per_litre',
        'amount', 'sms_sent', 'sms_status', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'fat_percent' => 'decimal:2',
            'snf_percent' => 'decimal:2',
            'quantity' => 'decimal:2',
            'rate_per_litre' => 'decimal:2',
            'amount' => 'decimal:2',
            'sms_sent' => 'boolean',
        ];
    }

    public function farmer()
    {
        return $this->belongsTo(Farmer::class)->withTrashed();
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
