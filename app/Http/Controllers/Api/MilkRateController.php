<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MilkRate;
use App\Models\RateHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MilkRateController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [
                'rates' => MilkRate::with('updatedBy')
                    ->orderBy('milk_type')
                    ->orderBy('fat_min')
                    ->orderBy('snf_min')
                    ->get(),
                'history' => RateHistory::with('changedBy')->latest()->take(10)->get(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.id' => 'nullable|integer|distinct|exists:milk_rates,id',
            'rates.*.milk_type' => 'required|in:cow,buffalo,a2',
            'rates.*.rate_per_litre' => 'required|numeric|min:0',
            'rates.*.fat_min' => 'required|numeric|min:0|max:15',
            'rates.*.fat_max' => 'required|numeric|min:0|max:15',
            'rates.*.snf_min' => 'required|numeric|min:0|max:15',
            'rates.*.snf_max' => 'required|numeric|min:0|max:15',
        ]);

        DB::transaction(function () use ($validated) {
            $slabs = $validated['rates'];
            foreach ($slabs as $index => $slab) {
                foreach (array_slice($slabs, $index + 1) as $other) {
                    abort_if($slab['milk_type'] === $other['milk_type'] && $slab['fat_min'] <= $other['fat_max'] && $slab['fat_max'] >= $other['fat_min'] && $slab['snf_min'] <= $other['snf_max'] && $slab['snf_max'] >= $other['snf_min'], 422, 'Rate slabs must not overlap.');
                }
            }
            $keptIds = [];

            foreach ($validated['rates'] as $rateData) {
                if ($rateData['fat_min'] > $rateData['fat_max'] || $rateData['snf_min'] > $rateData['snf_max']) {
                    abort(422, 'Fat/SNF minimum value cannot be greater than maximum value.');
                }

                $existing = ! empty($rateData['id'])
                    ? MilkRate::find($rateData['id'])
                    : null;

                if ($existing && $existing->rate_per_litre != $rateData['rate_per_litre']) {
                    RateHistory::create([
                        'milk_type' => $rateData['milk_type'],
                        'old_rate' => $existing->rate_per_litre,
                        'new_rate' => $rateData['rate_per_litre'],
                        'changed_by' => auth()->id(),
                    ]);
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

        return response()->json(['message' => 'Rates updated successfully.']);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'milk_type' => 'required|in:cow,buffalo,a2',
            'rate_chart' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $sheet = IOFactory::load($validated['rate_chart']->getRealPath())->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $snfHeaders = [];
        for ($column = 2; $column <= $highestColumnIndex; $column++) {
            $value = $sheet->getCell([$column, 2])->getCalculatedValue();
            if (is_numeric($value)) {
                $snfHeaders[$column] = (float) $value;
            }
        }

        $rates = [];
        $seen = [];
        for ($row = 3; $row <= $highestRow; $row++) {
            $fat = $sheet->getCell([1, $row])->getCalculatedValue();
            if (! is_numeric($fat)) {
                continue;
            }

            foreach ($snfHeaders as $column => $snf) {
                $rate = $sheet->getCell([$column, $row])->getCalculatedValue();
                if (! is_numeric($rate)) {
                    continue;
                }

                abort_if($fat < 0 || $fat > 15 || $snf < 0 || $snf > 15 || $rate < 0, 422, 'Chart contains invalid FAT, SNF or rate values.');
                $key = sprintf('%.2f:%.2f', $fat, $snf);
                abort_if(isset($seen[$key]), 422, 'Chart contains duplicate FAT/SNF combinations.');
                $seen[$key] = true;
                $rates[] = [
                    'milk_type' => $validated['milk_type'],
                    'fat_min' => (float) $fat,
                    'fat_max' => (float) $fat,
                    'snf_min' => (float) $snf,
                    'snf_max' => (float) $snf,
                    'rate_per_litre' => (float) $rate,
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (empty($rates)) {
            return response()->json(['message' => 'No rate rows found in the uploaded chart.'], 422);
        }

        DB::transaction(function () use ($validated, $rates) {
            MilkRate::where('milk_type', $validated['milk_type'])->delete();
            MilkRate::insert($rates);
        });

        return response()->json([
            'message' => count($rates).' rate slabs imported successfully.',
        ]);
    }
}
