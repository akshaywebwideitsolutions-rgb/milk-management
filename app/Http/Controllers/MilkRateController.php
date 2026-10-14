<?php

namespace App\Http\Controllers;

use App\Models\MilkRate;
use App\Models\RateHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilkRateController extends Controller
{
    public function index()
    {
        $rates = MilkRate::with('updatedBy')
            ->orderBy('milk_type')
            ->orderBy('fat_min')
            ->orderBy('snf_min')
            ->get();
        $history = RateHistory::with('changedBy')
            ->latest()
            ->take(10)
            ->get();

        return view('milk-rates.index', compact('rates', 'history'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.id' => 'nullable|integer|exists:milk_rates,id',
            'rates.*.milk_type' => 'required|in:cow,buffalo,a2',
            'rates.*.rate_per_litre' => 'required|numeric|min:0',
            'rates.*.fat_min' => 'required|numeric|min:0|max:15',
            'rates.*.fat_max' => 'required|numeric|min:0|max:15',
            'rates.*.snf_min' => 'required|numeric|min:0|max:15',
            'rates.*.snf_max' => 'required|numeric|min:0|max:15',
        ]);

        DB::transaction(function () use ($validated) {
            $keptIds = [];

            foreach ($validated['rates'] as $rateData) {
                if ($rateData['fat_min'] > $rateData['fat_max'] || $rateData['snf_min'] > $rateData['snf_max']) {
                    abort(422, 'Fat/SNF minimum value cannot be greater than maximum value.');
                }

                $existing = !empty($rateData['id'])
                    ? MilkRate::find($rateData['id'])
                    : null;

                if ($existing) {
                    $oldRate = $existing->rate_per_litre;

                    if ($oldRate != $rateData['rate_per_litre']) {
                        RateHistory::create([
                            'milk_type'  => $rateData['milk_type'],
                            'old_rate'   => $oldRate,
                            'new_rate'   => $rateData['rate_per_litre'],
                            'changed_by' => auth()->id(),
                        ]);
                    }

                }

                $rate = MilkRate::updateOrCreate(
                    ['id' => $rateData['id'] ?? null],
                    [
                        'milk_type' => $rateData['milk_type'],
                        'rate_per_litre' => $rateData['rate_per_litre'],
                        'fat_min' => $rateData['fat_min'],
                        'fat_max' => $rateData['fat_max'],
                        'snf_min' => $rateData['snf_min'],
                        'snf_max' => $rateData['snf_max'],
                        'updated_by' => auth()->id(),
                    ],
                );
                $keptIds[] = $rate->id;
            }

            MilkRate::whereNotIn('id', $keptIds)->delete();
        });

        return redirect()->route('milk-rates.index')->with('success', 'Rates updated successfully.');
    }
}
