<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\MilkCollection;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // KPI Cards
        $totalFarmers = Farmer::where('status', 'active')->count();
        $todayCollection = MilkCollection::where('collection_date', $today)->sum('quantity');
        $pendingInvoices = Invoice::whereIn('status', ['draft', 'pending'])->count();
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$thisMonth, now()])
            ->sum('net_amount');

        // Weekly collection data for chart (last 7 days)
        $weeklyData = MilkCollection::select(
                DB::raw('DATE(collection_date) as date'),
                DB::raw('SUM(quantity) as total_qty')
            )
            ->where('collection_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $weekLabels = [];
        $weekValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $dayLabel = now()->subDays($i)->format('D');
            $weekLabels[] = $dayLabel;
            $weekValues[] = (float) ($weeklyData[$d]->total_qty ?? 0);
        }

        // Recent activity (last 10 collections)
        $recentActivity = MilkCollection::with('farmer')
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($c) {
                return [
                    'farmer_name' => $c->farmer->name ?? 'Unknown',
                    'action'      => "Milk Entry — {$c->quantity}L " . ucfirst($c->milk_type),
                    'time'        => $c->created_at->diffForHumans(),
                    'amount'      => '₹' . number_format($c->amount, 0),
                ];
            });

        return view('dashboard.index', compact(
            'totalFarmers', 'todayCollection', 'pendingInvoices', 'monthlyRevenue',
            'weekLabels', 'weekValues', 'recentActivity'
        ));
    }
}
