<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\MilkCollection;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function generate(Request $request)
    {
        $reportType = $request->get('report_type', 'daily_collection');
        $startDate = $request->get('start_date', now()->subDays(15)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $milkType = $request->get('milk_type', '');

        $data = [];
        $chartData = [];
        $typeBreakdown = collect();
        $topFarmers = collect();

        if ($reportType === 'daily_collection') {
            $query = MilkCollection::select(
                    DB::raw('DATE(collection_date) as date'),
                    DB::raw('SUM(quantity) as total_qty'),
                    DB::raw('SUM(amount) as total_amount')
                )
                ->whereBetween('collection_date', [$startDate, $endDate]);

            if ($milkType) $query->where('milk_type', $milkType);

            $data = $query->groupBy('date')->orderBy('date')->get();

            $chartData['labels'] = $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'));
            $chartData['values'] = $data->pluck('total_qty')->map(fn($v) => (float) $v);

            // Milk type breakdown
            $typeBreakdown = MilkCollection::select('milk_type', DB::raw('SUM(quantity) as qty'))
                ->whereBetween('collection_date', [$startDate, $endDate])
                ->groupBy('milk_type')
                ->get();

            $totalQty = $typeBreakdown->sum('qty');
            $typeBreakdown = $typeBreakdown->map(function ($item) use ($totalQty) {
                return [
                    'milk_type' => ucfirst($item->milk_type),
                    'qty'       => $item->qty,
                    'percent'   => $totalQty > 0 ? round(($item->qty / $totalQty) * 100) : 0,
                ];
            });

            // Top 5 farmers
            $topFarmers = Farmer::select('farmers.name', DB::raw('SUM(milk_collections.quantity) as total_qty'))
                ->join('milk_collections', 'farmers.id', '=', 'milk_collections.farmer_id')
                ->whereBetween('milk_collections.collection_date', [$startDate, $endDate])
                ->groupBy('farmers.id', 'farmers.name')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();

        } elseif ($reportType === 'invoice_summary') {
            $data = Invoice::with(['farmer', 'billCycle'])
                ->whereBetween('period_start', [$startDate, $endDate])
                ->orderBy('id', 'desc')
                ->get();

        } elseif ($reportType === 'farmer_wise') {
            $data = Farmer::with(['collections' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('collection_date', [$startDate, $endDate]);
            }])
            ->where('status', 'active')
            ->get()
            ->map(function ($f) {
                return [
                    'farmer_id'   => $f->farmer_id,
                    'name'        => $f->name,
                    'total_litres' => $f->collections->sum('quantity'),
                    'total_amount' => $f->collections->sum('amount'),
                ];
            });
        }

        return view('reports.index', compact(
            'reportType', 'startDate', 'endDate', 'milkType',
            'data', 'chartData', 'typeBreakdown', 'topFarmers'
        ));
    }

    public function exportPdf(Request $request)
    {
        // Reuse generate logic and render to PDF
        $request->merge(['report_type' => $request->report_type ?? 'daily_collection']);
        // ... (would call generate and then render PDF)
        return redirect()->back()->with('info', 'PDF export ready — integrate with DomPDF.');
    }

    public function exportExcel(Request $request)
    {
        return redirect()->back()->with('info', 'Excel export ready — integrate with Laravel Excel.');
    }
}
