<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'farmer_id', 'milk_collection_id', 'phone', 'message', 'status', 'gateway_response',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function collection()
    {
        return $this->belongsTo(MilkCollection::class, 'milk_collection_id');
    }
}
