<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use Illuminate\Http\Request;

class FarmerHistoryController extends Controller
{
    public function index(Request $request)
    {
        $farmerId = $request->get('farmer_id');
        $farmer = $farmerId ? Farmer::findOrFail($farmerId) : null;
        $farmers = Farmer::orderBy('name')->get();

        if (!$farmer) {
            return view('farmer-history.index', compact('farmers', 'farmer'));
        }

        // KPI summary cards
        $totalCollection = $farmer->collections()->sum('quantity');
        $thisMonth = $farmer->totalCollectionLitres(now()->startOfMonth(), now());
        $pendingPayment = $farmer->pendingPaymentAmount();
        $avgDaily = $farmer->avgDailyLitres();

        // Collection history (last 10 entries)
        $collectionHistory = MilkCollection::where('farmer_id', $farmer->id)
            ->orderBy('collection_date', 'desc')
            ->take(10)
            ->get();

        // Payment / Invoice history
        $invoiceHistory = Invoice::where('farmer_id', $farmer->id)
            ->with('billCycle')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return view('farmer-history.index', compact(
            'farmers', 'farmer', 'totalCollection', 'thisMonth',
            'pendingPayment', 'avgDaily', 'collectionHistory', 'invoiceHistory'
        ));
    }
}
