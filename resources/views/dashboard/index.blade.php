@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-card-icon" style="background:#ECFDF5;color:#065F46;"><i class="fas fa-users"></i></div>
        <div class="kpi-card-body">
            <div class="kpi-value" id="kpi-total-farmers">0</div>
            <div class="kpi-label">Total Farmers</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card-icon" style="background:#EFF6FF;color:#1D4ED8;"><i class="fas fa-tint"></i></div>
        <div class="kpi-card-body">
            <div class="kpi-value" id="kpi-today-collection">0.0 L</div>
            <div class="kpi-label">Today Collection</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card-icon" style="background:#FEF3C7;color:#92400E;"><i class="fas fa-file-invoice"></i></div>
        <div class="kpi-card-body">
            <div class="kpi-value" id="kpi-pending-invoices">0</div>
            <div class="kpi-label">Pending Invoices</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card-icon" style="background:#D1FAE5;color:#065F46;"><i class="fas fa-trending-up"></i></div>
        <div class="kpi-card-body">
            <div class="kpi-value" id="kpi-monthly-revenue">₹0</div>
            <div class="kpi-label">Monthly Revenue</div>
        </div>
    </div>
</div>

<div class="chart-activity-row">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Collection Trend</h3>
            <span class="card-subtitle">This Week (Litres)</span>
        </div>
        <div class="chart-container" id="weekly-chart">
            <div class="bar-chart" id="weekly-chart-bars"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Recent Activity</h3></div>
        <div class="activity-list" id="recent-activity-list">
            <div style="text-align:center;padding:40px;color:#94A3B8;">Loading activity...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN');

    try {
        const response = await DairyApp.api('/api/dashboard');
        const data = response.data;

        document.getElementById('kpi-total-farmers').textContent = Number(data.total_farmers || 0).toLocaleString('en-IN');
        document.getElementById('kpi-today-collection').textContent = `${Number(data.today_collection || 0).toFixed(1)} L`;
        document.getElementById('kpi-pending-invoices').textContent = Number(data.pending_invoices || 0).toLocaleString('en-IN');
        document.getElementById('kpi-monthly-revenue').textContent = money(data.monthly_revenue);

        const maxQty = Math.max(...data.weekly_collection.map((day) => Number(day.quantity || 0)), 1);
        document.getElementById('weekly-chart-bars').innerHTML = data.weekly_collection.map((day) => `
            <div class="bar-col">
                <div class="bar-value">${Number(day.quantity || 0).toFixed(1)}</div>
                <div class="bar-fill" style="height:${(Number(day.quantity || 0) / maxQty) * 100}%"></div>
                <div class="bar-label">${day.label}</div>
            </div>
        `).join('');

        document.getElementById('recent-activity-list').innerHTML = data.recent_activity.length
            ? data.recent_activity.map((activity) => `
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="activity-info">
                        <div class="activity-name">${DairyApp.escapeHtml(activity.farmer_name)}</div>
                        <div class="activity-desc">${DairyApp.escapeHtml(activity.action)}</div>
                    </div>
                    <div class="activity-meta">
                        <div class="activity-amount">${money(activity.amount)}</div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
                </div>
            `).join('')
            : '<div style="text-align:center;padding:40px;color:#94A3B8;">No recent activity</div>';
    } catch (error) {
        DairyApp.flash(error.message, 'error');
    }
});
</script>
@endpush
