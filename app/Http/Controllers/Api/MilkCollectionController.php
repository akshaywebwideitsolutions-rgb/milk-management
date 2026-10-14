<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use App\Models\MilkRate;
use App\Services\InvoiceService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilkCollectionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $session = $request->query('session', now()->hour < 12 ? 'morning' : 'evening');
        if ($session === 'auto') {
            $session = now()->hour < 12 ? 'morning' : 'evening';
        }

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
            'fat_percent' => 'required|numeric|min:0|max:15',
            'snf_percent' => 'required|numeric|min:0|max:15',
            'quantity' => 'required|numeric|min:0.1|max:200',
            'send_sms' => 'boolean',
            'request_id' => 'required|uuid',
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

        $collection = DB::transaction(function () use ($farmer, $validated, $fatPercent, $snfPercent, $rate, $amount) {
            Farmer::whereKey($farmer->id)->lockForUpdate()->firstOrFail();
            $existing = MilkCollection::where('request_id', $validated['request_id'])->first();
            if ($existing) {
                foreach (['farmer_id', 'session', 'quantity', 'fat_percent', 'snf_percent'] as $field) {
                    abort_if((string) $existing->$field != (string) $validated[$field], 409, 'This request ID was already used for another delivery.');
                }
                abort_if($existing->collection_date->toDateString() !== Carbon::parse($validated['collection_date'])->toDateString(), 409, 'This request ID was already used for another date.');

                return $existing;
            }
            abort_if(Invoice::where('farmer_id', $farmer->id)->whereIn('status', ['sent', 'paid'])->where('period_start', '<=', $validated['collection_date'])->where('period_end', '>=', $validated['collection_date'])->exists(), 422, 'This period has a finalized invoice.');

            return MilkCollection::create([
                'request_id' => $validated['request_id'],
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

        });

        foreach (Invoice::where('farmer_id', $farmer->id)->whereIn('status', ['draft', 'pending'])->where('period_start', '<=', $validated['collection_date'])->where('period_end', '>=', $validated['collection_date'])->get() as $invoice) {
            app(InvoiceService::class)->refresh($invoice);
        }

        if ($collection->wasRecentlyCreated && ! empty($validated['send_sms'])) {
            try {
                $sent = app(SmsService::class)->sendCollectionSMS($collection, $farmer);
                $collection->update([
                    'sms_sent' => $sent,
                    'sms_status' => $sent ? 'sent' : 'failed',
                ]);
            } catch (\Throwable $e) {
                $collection->update(['sms_status' => 'failed: '.$e->getMessage()]);
            }
        }

        return response()->json([
            'message' => 'Milk collection entry saved.',
            'data' => $collection->fresh('farmer'),
        ], $collection->wasRecentlyCreated ? 201 : 200);
    }

    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'fat_percent' => 'required|numeric|min:0|max:15',
            'snf_percent' => 'required|numeric|min:0|max:15',
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
