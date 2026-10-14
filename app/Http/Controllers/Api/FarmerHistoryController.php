<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use Illuminate\Http\Request;

class FarmerHistoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $farmerId = $request->query('farmer_id');
        $farmer = $farmerId ? Farmer::findOrFail($farmerId) : null;

        $data = [
            'farmers' => Farmer::orderBy('name')->get(),
            'farmer' => $farmer,
        ];

        if ($farmer) {
            $data += [
                'total_collection' => (float) $farmer->collections()->sum('quantity'),
                'this_month' => (float) $farmer->totalCollectionLitres(now()->startOfMonth(), now()),
                'pending_payment' => (float) $farmer->pendingPaymentAmount(),
                'avg_daily' => (float) $farmer->avgDailyLitres(),
                'collection_history' => MilkCollection::where('farmer_id', $farmer->id)
                    ->orderByDesc('collection_date')
                    ->take(10)
                    ->get(),
                'invoice_history' => Invoice::where('farmer_id', $farmer->id)
                    ->with('billCycle')
                    ->orderByDesc('id')
                    ->take(10)
                    ->get(),
            ];
        }

        return response()->json(['data' => $data]);
    }
}
