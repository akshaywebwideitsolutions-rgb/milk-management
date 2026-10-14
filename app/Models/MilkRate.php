<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilkRate extends Model
{
    protected $table = 'milk_rates';

    protected $fillable = [
        'milk_type', 'rate_per_litre', 'fat_min', 'fat_max', 'snf_min', 'snf_max', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'milk_type' => \App\Enums\MilkType::class,
            'rate_per_litre' => 'decimal:2',
            'fat_min' => 'decimal:2',
            'fat_max' => 'decimal:2',
            'snf_min' => 'decimal:2',
            'snf_max' => 'decimal:2',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function findRate(string $milkType, ?float $fatPercent = null, ?float $snfPercent = null): ?self
    {
        $query = static::where('milk_type', $milkType);

        if ($fatPercent !== null && $snfPercent !== null) {
            $query->where('fat_min', '<=', $fatPercent)
                ->where('fat_max', '>=', $fatPercent)
                ->where('snf_min', '<=', $snfPercent)
                ->where('snf_max', '>=', $snfPercent);
        }

        return $query
            ->orderBy('fat_min')
            ->orderBy('snf_min')
            ->first();
    }

    public static function getRate(string $milkType, ?float $fatPercent = null, ?float $snfPercent = null): ?float
    {
        return optional(static::findRate($milkType, $fatPercent, $snfPercent))->rate_per_litre;
    }
}
