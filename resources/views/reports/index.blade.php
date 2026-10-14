@extends('layouts.app')
@section('title', 'Reports & Analytics')
@section('page-title', 'Reports & Analytics')

@section('content')
<div class="card report-filter-bar">
    <form id="report-form">
        <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Report Type</label>
                <select name="report_type" class="form-input" style="width:190px;">
                    <option value="daily_collection">Daily Collection Summary</option>
                    <option value="invoice_summary">Invoice Summary</option>
                    <option value="farmer_wise">Farmer-wise Report</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Date Range</label>
                <div style="display:flex;gap:6px;align-items:center;">
                    <input type="date" name="start_date" class="form-input" style="width:150px;">
                    <span style="color:#94A3B8;">-</span>
                    <input type="date" name="end_date" class="form-input" style="width:150px;">
                </div>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Milk Type</label>
                <select name="milk_type" class="form-input" style="width:120px;">
                    <option value="">All</option>
                    <option value="cow">Cow</option>
                    <option value="buffalo">Buffalo</option>
                    <option value="a2">A2</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-chart-bar"></i> Generate Report</button>
            <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fas fa-file-pdf"></i> Print</button>
        </div>
    </form>
</div>

<div id="report-output" style="margin-top:20px;">
    <div class="card" style="text-align:center;padding:50px;color:#94A3B8;">Loading report...</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('report-form');
    const output = document.getElementById('report-output');
    const params = new URLSearchParams(window.location.search);
    const colors = { Cow: '#10B981', Buffalo: '#0EA5E9', A2: '#F59E0B', cow: '#10B981', buffalo: '#0EA5E9', a2: '#F59E0B' };

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN');
    const date = (value, options = { day: '2-digit', month: 'short', year: 'numeric' }) => value
        ? new Intl.DateTimeFormat('en-IN', options).format(new Date(value))
        : '-';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    function defaultDate(daysAgo) {
        const value = new Date();
        value.setDate(value.getDate() - daysAgo);
        return value.toISOString().slice(0, 10);
    }

    form.report_type.value = params.get('report_type') || 'daily_collection';
    form.start_date.value = params.get('start_date') || defaultDate(15);
    form.end_date.value = params.get('end_date') || defaultDate(0);
    form.milk_type.value = params.get('milk_type') || '';

    function renderDaily(payload) {
        const data = payload.data || [];
        const chart = payload.chart_data || { labels: [], values: [] };
        const values = chart.values || [];
        const maxValue = Math.max(...values.map(Number), 1);

        output.innerHTML = `
            <div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:20px;">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Daily Collection (Litres)</h3></div>
                    <div class="chart-container" style="padding:20px;">
                        <div class="bar-chart" style="height:260px;align-items:flex-end;">
                            ${(chart.labels || []).map((label, index) => `
                                <div class="bar-col">
                                    <div class="bar-value" style="font-size:9px;">${Number(values[index] || 0).toLocaleString('en-IN')}</div>
                                    <div class="bar-fill" style="height:${(Number(values[index] || 0) / maxValue) * 100}%"></div>
                                    <div class="bar-label" style="font-size:9px;">${escapeHtml(label)}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Milk Type Breakdown</h3></div>
                        ${(payload.type_breakdown || []).length ? payload.type_breakdown.map((item) => `
                            <div style="margin-bottom:12px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                    <span style="font-size:13px;font-weight:600;">${escapeHtml(item.milk_type)}</span>
                                    <span style="font-size:13px;font-weight:700;">${Number(item.percent || 0)}%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:${Number(item.percent || 0)}%;background:${colors[item.milk_type] || '#10B981'};"></div>
                                </div>
                            </div>
                        `).join('') : '<div style="color:#94A3B8;text-align:center;padding:20px;">No breakdown data</div>'}
                    </div>

                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Top 5 Farmers by Volume</h3></div>
                        ${(payload.top_farmers || []).length ? payload.top_farmers.map((farmer, index) => `
                            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;${index < 4 ? 'border-bottom:1px solid #F1F5F9;' : ''}">
                                <span style="font-size:12px;font-weight:700;color:#94A3B8;width:20px;">${index + 1}.</span>
                                <span style="flex:1;font-size:13px;font-weight:500;">${escapeHtml(farmer.name)}</span>
                                <span style="font-size:13px;font-weight:700;color:#0F172A;">${Number(farmer.total_qty || 0).toLocaleString('en-IN')}L</span>
                            </div>
                        `).join('') : '<div style="color:#94A3B8;text-align:center;padding:20px;">No farmer data</div>'}
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:20px;">
                <div class="card-header"><h3 class="card-title">Daily Summary Data</h3></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Date</th><th>Total Litres</th><th>Total Amount</th></tr></thead>
                        <tbody>
                            ${data.length ? data.map((row) => `
                                <tr>
                                    <td>${date(row.date, { day: '2-digit', month: 'short', year: 'numeric', weekday: 'short' })}</td>
                                    <td><strong>${Number(row.total_qty || 0).toFixed(1)} L</strong></td>
                                    <td>${money(row.total_amount)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="3" style="text-align:center;padding:30px;color:#94A3B8;">No data</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderInvoiceSummary(payload) {
        const rows = payload.data || [];
        output.innerHTML = `
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Invoice #</th><th>Farmer</th><th>Period</th><th>Litres</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
                        <tbody>
                            ${rows.length ? rows.map((invoice) => `
                                <tr>
                                    <td>${escapeHtml(invoice.invoice_no || '-')}</td>
                                    <td>${escapeHtml(invoice.farmer?.name || '-')}</td>
                                    <td>${date(invoice.period_start, { day: '2-digit', month: 'short' })} - ${date(invoice.period_end)}</td>
                                    <td>${Number(invoice.total_litres || 0).toLocaleString('en-IN')}L</td>
                                    <td>${money(invoice.gross_amount)}</td>
                                    <td style="color:#EF4444;">${money(invoice.total_deductions)}</td>
                                    <td style="font-weight:700;color:#10B981;">${money(invoice.net_amount)}</td>
                                    <td><span class="badge-status badge-${escapeHtml(invoice.status || 'pending')}">${escapeHtml(invoice.status || 'pending')}</span></td>
                                </tr>
                            `).join('') : '<tr><td colspan="8" style="text-align:center;padding:30px;color:#94A3B8;">No invoices found</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderFarmerWise(payload) {
        const rows = payload.data || [];
        output.innerHTML = `
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Farmer ID</th><th>Name</th><th>Total Litres</th><th>Total Amount</th></tr></thead>
                        <tbody>
                            ${rows.length ? rows.map((farmer) => `
                                <tr>
                                    <td>${escapeHtml(farmer.farmer_id || '-')}</td>
                                    <td>${escapeHtml(farmer.name || '-')}</td>
                                    <td>${Number(farmer.total_litres || 0).toFixed(1)} L</td>
                                    <td style="font-weight:700;">${money(farmer.total_amount)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="4" style="text-align:center;padding:30px;color:#94A3B8;">No farmers found</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    async function loadReport() {
        output.innerHTML = '<div class="card" style="text-align:center;padding:50px;color:#94A3B8;">Loading report...</div>';

        try {
            const query = new URLSearchParams(DairyApp.formPayload(form));
            const response = await DairyApp.api(`/api/reports?${query.toString()}`);
            const payload = response.data || response;

            if (payload.report_type === 'invoice_summary') {
                renderInvoiceSummary(payload);
            } else if (payload.report_type === 'farmer_wise') {
                renderFarmerWise(payload);
            } else {
                renderDaily(payload);
            }
        } catch (error) {
            output.innerHTML = '<div class="card" style="text-align:center;padding:50px;color:#DC2626;">Unable to load report</div>';
            DairyApp.flash(error.message, 'error');
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadReport();
    });

    loadReport();
});
</script>
@endpush
