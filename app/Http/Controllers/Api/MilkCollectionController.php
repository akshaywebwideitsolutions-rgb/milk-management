<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\MilkCollection;
use App\Models\MilkRate;
use App\Services\SmsService;
use Illuminate\Http\Request;

class MilkCollectionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $session = $request->query('session', 'morning');

        $entries = MilkCollection::with('farmer')
            ->where('collection_date', $date)
            ->where('session', $session)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'date' => $date,
                'session' => $session,
                'entries' => $entries,
                'totals' => [
                    'quantity' => (float) $entries->sum('quantity'),
                    'amount' => (float) $entries->sum('amount'),
                ],
                'farmers' => Farmer::where('status', 'active')->orderBy('name')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'collection_date' => 'required|date',
            'session' => 'required|in:morning,evening',
            'fat_percent' => 'nullable|numeric|min:0|max:15',
            'snf_percent' => 'nullable|numeric|min:0|max:15',
            'quantity' => 'required|numeric|min:0.1|max:200',
            'send_sms' => 'boolean',
        ]);

        $farmer = Farmer::findOrFail($validated['farmer_id']);
        $fatPercent = (float) ($validated['fat_percent'] ?? 0);
        $snfPercent = (float) ($validated['snf_percent'] ?? 0);
        $rate = MilkRate::getRate($farmer->milk_type->value, $fatPercent, $snfPercent);

        if ($rate === null) {
            return response()->json([
                'message' => 'No milk rate found for this milk type, FAT, and SNF combination.',
            ], 422);
        }

        $amount = round($validated['quantity'] * $rate, 2);

        $collection = MilkCollection::create([
            'farmer_id' => $farmer->id,
            'collection_date' => $validated['collection_date'],
            'session' => $validated['session'],
            'milk_type' => $farmer->milk_type->value,
            'fat_percent' => $fatPercent,
            'snf_percent' => $snfPercent,
            'quantity' => $validated['quantity'],
            'rate_per_litre' => $rate,
            'amount' => $amount,
            'sms_sent' => false,
            'recorded_by' => auth()->id(),
        ]);

        if (!empty($validated['send_sms'])) {
            try {
                $sent = app(SmsService::class)->sendCollectionSMS($collection, $farmer);
                $collection->update([
                    'sms_sent' => $sent,
                    'sms_status' => $sent ? 'sent' : 'failed',
                ]);
            } catch (\Throwable $e) {
                $collection->update(['sms_status' => 'failed: ' . $e->getMessage()]);
            }
        }

        return response()->json([
            'message' => 'Milk collection entry saved.',
            'data' => $collection->fresh('farmer'),
        ], 201);
    }

    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'fat_percent' => 'nullable|numeric|min:0|max:15',
            'snf_percent' => 'nullable|numeric|min:0|max:15',
        ]);

        $farmer = Farmer::findOrFail($validated['farmer_id']);
        $rate = MilkRate::getRate(
            $farmer->milk_type->value,
            isset($validated['fat_percent']) ? (float) $validated['fat_percent'] : null,
            isset($validated['snf_percent']) ? (float) $validated['snf_percent'] : null,
        ) ?? 0;

        return response()->json(['data' => ['rate' => (float) $rate]]);
    }
}
