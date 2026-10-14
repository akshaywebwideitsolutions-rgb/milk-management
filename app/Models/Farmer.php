<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Farmer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farmer_id', 'name', 'phone', 'village', 'milk_type',
        'status', 'address', 'bank_name', 'bank_account', 'bank_ifsc', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'milk_type' => \App\Enums\MilkType::class,
            'status' => \App\Enums\FarmerStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Farmer $farmer) {
            if (empty($farmer->farmer_id)) {
                $last = static::withTrashed()->orderBy('id', 'desc')->first();
                $next = $last ? ((int) substr($last->farmer_id, 2)) + 1 : 1;
                $farmer->farmer_id = 'F-' . str_pad($next, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ──
    public function collections()
    {
        return $this->hasMany(MilkCollection::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    // ── Helpers ──
    public function totalCollectionLitres($startDate = null, $endDate = null)
    {
        $q = $this->collections();
        if ($startDate) $q->where('collection_date', '>=', $startDate);
        if ($endDate) $q->where('collection_date', '<=', $endDate);
        return $q->sum('quantity');
    }

    public function totalCollectionAmount($startDate = null, $endDate = null)
    {
        $q = $this->collections();
        if ($startDate) $q->where('collection_date', '>=', $startDate);
        if ($endDate) $q->where('collection_date', '<=', $endDate);
        return $q->sum('amount');
    }

    public function pendingPaymentAmount()
    {
        return $this->invoices()->whereIn('status', ['pending', 'sent'])->sum('net_amount');
    }

    public function avgDailyLitres($days = 30)
    {
        $from = now()->subDays($days);
        $total = $this->collections()->where('collection_date', '>=', $from)->sum('quantity');
        return round($total / $days, 1);
    }
}
