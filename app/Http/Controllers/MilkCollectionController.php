<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\MilkCollection;
use App\Models\MilkRate;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilkCollectionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $session = $request->get('session', 'morning');

        $entries = MilkCollection::with('farmer')
            ->where('collection_date', $date)
            ->where('session', $session)
            ->orderBy('id', 'desc')
            ->get();

        $totals = [
            'quantity' => $entries->sum('quantity'),
            'amount'   => $entries->sum('amount'),
        ];

        $farmers = Farmer::where('status', 'active')->orderBy('name')->get();

        return view('milk-collection.index', compact('entries', 'date', 'session', 'totals', 'farmers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farmer_id'     => 'required|exists:farmers,id',
            'collection_date' => 'required|date',
            'session'       => 'required|in:morning,evening',
            'fat_percent'   => 'nullable|numeric|min:0|max:15',
            'snf_percent'   => 'nullable|numeric|min:0|max:15',
            'quantity'      => 'required|numeric|min:0.1|max:200',
            'send_sms'      => 'boolean',
        ]);

        $farmer = Farmer::findOrFail($validated['farmer_id']);
        $fatPercent = (float) ($validated['fat_percent'] ?? 0);
        $snfPercent = (float) ($validated['snf_percent'] ?? 0);
        $rate = MilkRate::getRate($farmer->milk_type->value, $fatPercent, $snfPercent);

        if ($rate === null) {
            return redirect()->back()->withErrors([
                'rate' => 'No milk rate found for this milk type, FAT, and SNF combination.',
            ]);
        }

        $amount = round($validated['quantity'] * $rate, 2);

        $collection = MilkCollection::create([
            'farmer_id'      => $farmer->id,
            'collection_date' => $validated['collection_date'],
            'session'        => $validated['session'],
            'milk_type'      => $farmer->milk_type->value,
            'fat_percent'    => $fatPercent,
            'snf_percent'    => $snfPercent,
            'quantity'       => $validated['quantity'],
            'rate_per_litre' => $rate,
            'amount'         => $amount,
            'sms_sent'       => false,
            'recorded_by'    => auth()->id(),
        ]);

        // Send SMS if requested
        if (!empty($validated['send_sms'])) {
            try {
                $smsService = app(SmsService::class);
                $sent = $smsService->sendCollectionSMS($collection, $farmer);
                $collection->update([
                    'sms_sent' => $sent,
                    'sms_status' => $sent ? 'sent' : 'failed',
                ]);
            } catch (\Throwable $e) {
                $collection->update(['sms_status' => 'failed: ' . $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', "Entry saved — {$farmer->name}, {$validated['quantity']}L, ₹{$amount}");
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

        return response()->json(['rate' => $rate]);
    }
}
