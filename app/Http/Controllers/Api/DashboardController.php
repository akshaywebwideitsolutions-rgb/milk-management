<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth();

        $weeklyData = MilkCollection::select(
            DB::raw('DATE(collection_date) as date'),
            DB::raw('SUM(quantity) as total_qty')
        )
            ->where('collection_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $week[] = [
                'date' => $key,
                'label' => $date->format('D'),
                'quantity' => (float) ($weeklyData[$key]->total_qty ?? 0),
            ];
        }

        return response()->json([
            'data' => [
                'total_farmers' => Farmer::where('status', 'active')->count(),
                'today_collection' => (float) MilkCollection::where('collection_date', $today)->sum('quantity'),
                'pending_invoices' => Invoice::whereIn('status', ['draft', 'pending'])->count(),
                'monthly_revenue' => (float) Invoice::where('status', 'paid')
                    ->whereBetween('paid_date', [$thisMonth, now()])
                    ->sum('net_amount'),
                'weekly_collection' => $week,
                'recent_activity' => MilkCollection::with('farmer')
                    ->latest()
                    ->take(8)
                    ->get()
                    ->map(fn ($collection) => [
                        'farmer_name' => $collection->farmer->name ?? 'Unknown',
                        'action' => 'Milk Entry - ' . $collection->quantity . 'L ' . ucfirst((string) $collection->milk_type),
                        'time' => $collection->created_at->diffForHumans(),
                        'amount' => (float) $collection->amount,
                    ]),
            ],
        ]);
    }
}
