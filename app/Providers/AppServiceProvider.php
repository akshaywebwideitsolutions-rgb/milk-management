<?php

namespace App\Providers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 15);

            $defaults = [
                'apiBaseUrl' => config('nativephp-internal.running')
                    ? config('app.desktop_api_base_url')
                    : (config('app.api_base_url') ?: url('/api')),
                'totalFarmers' => 0,
                'todayCollection' => 0,
                'pendingInvoices' => 0,
                'monthlyRevenue' => 0,
                'weekLabels' => [],
                'weekValues' => [],
                'recentActivity' => collect(),
                'farmers' => $emptyPaginator,
                'entries' => collect(),
                'date' => now()->toDateString(),
                'session' => 'morning',
                'totals' => ['quantity' => 0, 'amount' => 0],
                'rates' => collect(),
                'history' => collect(),
                'invoices' => $emptyPaginator,
                'cycles' => collect(),
                'currentCycle' => null,
                'deductions' => $emptyPaginator,
                'totalThisCycle' => 0,
                'farmer' => null,
                'reportType' => 'daily_collection',
                'startDate' => now()->subDays(15)->toDateString(),
                'endDate' => now()->toDateString(),
                'milkType' => '',
                'data' => collect(),
                'chartData' => [],
                'typeBreakdown' => collect(),
                'topFarmers' => collect(),
            ];

            $existing = $view->getData();
            $view->with(array_diff_key($defaults, $existing));
        });
    }
}
