<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateHistory extends Model
{
    protected $table = 'rate_history';

    protected $fillable = [
        'milk_type', 'old_rate', 'new_rate', 'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'old_rate' => 'decimal:2',
            'new_rate' => 'decimal:2',
        ];
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
