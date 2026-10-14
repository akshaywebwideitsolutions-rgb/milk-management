<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $reportType = $request->query('report_type', 'daily_collection');
        $startDate = $request->query('start_date', now()->subDays(15)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $milkType = $request->query('milk_type', '');

        $payload = [
            'report_type' => $reportType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'milk_type' => $milkType,
            'data' => [],
            'chart_data' => [],
            'type_breakdown' => [],
            'top_farmers' => [],
        ];

        if ($reportType === 'daily_collection') {
            $query = MilkCollection::select(
                DB::raw('DATE(collection_date) as date'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(amount) as total_amount')
            )->whereBetween('collection_date', [$startDate, $endDate]);

            if ($milkType) {
                $query->where('milk_type', $milkType);
            }

            $data = $query->groupBy('date')->orderBy('date')->get();
            $typeBreakdown = MilkCollection::select('milk_type', DB::raw('SUM(quantity) as qty'))
                ->whereBetween('collection_date', [$startDate, $endDate])
                ->groupBy('milk_type')
                ->get();
            $totalQty = $typeBreakdown->sum('qty');

            $payload['data'] = $data;
            $payload['chart_data'] = [
                'labels' => $data->pluck('date')->map(fn ($date) => \Carbon\Carbon::parse($date)->format('d M')),
                'values' => $data->pluck('total_qty')->map(fn ($value) => (float) $value),
            ];
            $payload['type_breakdown'] = $typeBreakdown->map(fn ($item) => [
                'milk_type' => ucfirst($item->milk_type),
                'qty' => (float) $item->qty,
                'percent' => $totalQty > 0 ? round(($item->qty / $totalQty) * 100) : 0,
            ]);
            $payload['top_farmers'] = Farmer::select('farmers.name', DB::raw('SUM(milk_collections.quantity) as total_qty'))
                ->join('milk_collections', 'farmers.id', '=', 'milk_collections.farmer_id')
                ->whereBetween('milk_collections.collection_date', [$startDate, $endDate])
                ->groupBy('farmers.id', 'farmers.name')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();
        } elseif ($reportType === 'invoice_summary') {
            $payload['data'] = Invoice::with(['farmer', 'billCycle'])
                ->whereBetween('period_start', [$startDate, $endDate])
                ->orderByDesc('id')
                ->get();
        } elseif ($reportType === 'farmer_wise') {
            $payload['data'] = Farmer::with(['collections' => fn ($q) => $q->whereBetween('collection_date', [$startDate, $endDate])])
                ->where('status', 'active')
                ->get()
                ->map(fn ($farmer) => [
                    'farmer_id' => $farmer->farmer_id,
                    'name' => $farmer->name,
                    'total_litres' => (float) $farmer->collections->sum('quantity'),
                    'total_amount' => (float) $farmer->collections->sum('amount'),
                ]);
        }

        return response()->json(['data' => $payload]);
    }
}
