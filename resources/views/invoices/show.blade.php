@extends('layouts.app')
@section('title', 'Invoice Details')
@section('page-title', 'Invoice Details')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Invoice Details</h3>
            <a id="invoice-print-link" href="#" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Print</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <tbody id="invoice-detail-body">
                    <tr><td colspan="2" style="text-align:center;padding:30px;color:#94A3B8;">Loading invoice...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><h3 class="card-title">Milk Collections</h3></div>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Session</th><th>Fat</th><th>SNF</th><th>Qty(L)</th><th>Rate</th><th>Amount</th></tr></thead>
                    <tbody id="invoice-collections-body"></tbody>
                    <tfoot>
                        <tr style="background:#ECFDF5;font-weight:700;">
                            <td colspan="4">Total Milk</td>
                            <td id="invoice-total-milk">-</td>
                            <td></td>
                            <td id="invoice-total-gross">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Deductions</h3></div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Type</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody id="invoice-deductions-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const id = window.location.pathname.match(/\/invoices\/(\d+)/)?.[1];
    if (!id) return;

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN');
    const litres = (value) => Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' L';
    const date = (value) => value ? new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';

    try {
        const response = await DairyApp.api(`/api/invoices/${id}`);
        const { invoice, collections, deductions } = response.data;
        document.getElementById('invoice-total-milk').textContent = litres(invoice.total_litres);
        document.getElementById('invoice-total-gross').textContent = money(invoice.gross_amount);

        document.getElementById('invoice-print-link').href = DairyApp.webUrl(`/invoices/${id}/print`);
        document.getElementById('invoice-detail-body').innerHTML = `
            <tr><td style="color:#64748B;">Invoice No</td><td><strong>${DairyApp.escapeHtml(invoice.invoice_no)}</strong></td></tr>
            <tr><td style="color:#64748B;">Farmer</td><td>${DairyApp.escapeHtml(invoice.farmer?.name || '-')}</td></tr>
            <tr><td style="color:#64748B;">Period</td><td>${date(invoice.period_start)} - ${date(invoice.period_end)}</td></tr>
            <tr><td style="color:#64748B;">Status</td><td><span class="badge-status badge-${invoice.status}">${invoice.status}</span></td></tr>
            <tr><td style="color:#64748B;font-weight:700;">Total Milk</td><td style="font-size:18px;font-weight:800;">${litres(invoice.total_litres)}</td></tr>
            <tr><td style="color:#64748B;">Gross Amount</td><td>${money(invoice.gross_amount)}</td></tr>
            <tr><td style="color:#64748B;">Deductions</td><td style="color:#EF4444;">- ${money(invoice.total_deductions)}</td></tr>
            <tr><td style="color:#64748B;">Net Payable</td><td style="color:#10B981;">${money(invoice.net_amount)}</td></tr>
        `;

        document.getElementById('invoice-collections-body').innerHTML = collections.length
            ? collections.map((collection) => `
                <tr>
                    <td>${date(collection.collection_date)}</td>
                    <td><span class="badge-status badge-green" style="font-size:10px;">${collection.session}</span></td>
                    <td>${Number(collection.fat_percent || 0).toFixed(1)}</td>
                    <td>${Number(collection.snf_percent || 0).toFixed(1)}</td>
                    <td>${Number(collection.quantity || 0).toFixed(1)}</td>
                    <td>${money(collection.rate_per_litre)}</td>
                    <td>${money(collection.amount)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="7" style="text-align:center;padding:20px;color:#94A3B8;">No collections</td></tr>';

        document.getElementById('invoice-deductions-body').innerHTML = deductions.length
            ? deductions.map((deduction) => `
                <tr>
                    <td>${DairyApp.escapeHtml(deduction.deduction_type)}</td>
                    <td style="color:#EF4444;">- ${money(deduction.amount)}</td>
                    <td>${date(deduction.deduction_date)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="3" style="text-align:center;padding:20px;color:#94A3B8;">No deductions</td></tr>';
    } catch (error) {
        DairyApp.flash(error.message, 'error');
    }
});
</script>
@endpush
