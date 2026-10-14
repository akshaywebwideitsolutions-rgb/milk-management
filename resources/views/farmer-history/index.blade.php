@extends('layouts.app')
@section('title', 'Farmer History')
@section('page-title', 'Farmer History')

@section('content')
<div class="page-header-bar">
    <select id="farmer-select" class="form-input" style="width:280px;">
        <option value="">- Select Farmer -</option>
    </select>
    <a href="#" id="farmer-profile-link" class="btn btn-outline" style="display:none;"><i class="fas fa-user"></i> View Full Profile</a>
</div>

<div id="empty-state" class="card" style="margin-top:16px;text-align:center;padding:60px;">
    <i class="fas fa-history" style="font-size:48px;color:#CBD5E1;margin-bottom:16px;"></i>
    <p style="color:#64748B;">Select a farmer to view their complete history</p>
</div>

<div id="history-content" style="display:none;">
    <div class="kpi-grid" style="margin-top:16px;">
        <div class="kpi-card">
            <div class="kpi-value" id="kpi-total-collection">0 L</div>
            <div class="kpi-label">Total Collection</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value" id="kpi-this-month">0.0 L</div>
            <div class="kpi-label">This Month</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value" style="color:#EF4444;" id="kpi-pending-payment">₹0</div>
            <div class="kpi-label">Pending Payment</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value" id="kpi-avg-daily">0 L</div>
            <div class="kpi-label">Avg Daily</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:20px;margin-top:20px;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Collection History</h3></div>
            <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Morning (L)</th><th>Evening (L)</th><th>Total (L)</th><th>Rate</th></tr>
                    </thead>
                    <tbody id="collection-history-body">
                        <tr><td colspan="5" style="text-align:center;padding:20px;color:#94A3B8;">Loading collections...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment History</h3></div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Invoice #</th><th>Period</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody id="invoice-history-body">
                        <tr><td colspan="4" style="text-align:center;padding:20px;color:#94A3B8;">Loading invoices...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const farmerSelect = document.getElementById('farmer-select');
    const profileLink = document.getElementById('farmer-profile-link');
    const emptyState = document.getElementById('empty-state');
    const historyContent = document.getElementById('history-content');
    const collectionBody = document.getElementById('collection-history-body');
    const invoiceBody = document.getElementById('invoice-history-body');
    const params = new URLSearchParams(window.location.search);

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN');
    const date = (value) => value ? new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    function renderFarmers(farmers, selectedId) {
        farmerSelect.innerHTML = '<option value="">- Select Farmer -</option>' + farmers.map((farmer) => `
            <option value="${farmer.id}" ${String(farmer.id) === String(selectedId) ? 'selected' : ''}>
                ${escapeHtml(farmer.name)} - ${escapeHtml(farmer.farmer_id)}
            </option>
        `).join('');
    }

    function groupedCollections(collections) {
        return collections.reduce((groups, collection) => {
            const key = collection.collection_date?.slice(0, 10) || collection.collection_date || '-';
            groups[key] = groups[key] || [];
            groups[key].push(collection);
            return groups;
        }, {});
    }

    function renderCollections(collections) {
        const groups = groupedCollections(collections);
        const rows = Object.entries(groups).map(([collectionDate, entries]) => {
            const morning = entries.filter((entry) => entry.session === 'morning').reduce((sum, entry) => sum + Number(entry.quantity || 0), 0);
            const evening = entries.filter((entry) => entry.session === 'evening').reduce((sum, entry) => sum + Number(entry.quantity || 0), 0);
            const total = entries.reduce((sum, entry) => sum + Number(entry.quantity || 0), 0);
            const rate = entries[0]?.rate_per_litre || 0;

            return `
                <tr>
                    <td>${date(collectionDate)}</td>
                    <td>${morning.toFixed(1)}</td>
                    <td>${evening.toFixed(1)}</td>
                    <td><strong>${total.toFixed(1)}</strong></td>
                    <td>${money(rate)}</td>
                </tr>
            `;
        });

        collectionBody.innerHTML = rows.length
            ? rows.join('')
            : '<tr><td colspan="5" style="text-align:center;padding:20px;color:#94A3B8;">No collections</td></tr>';
    }

    function renderInvoices(invoices) {
        invoiceBody.innerHTML = invoices.length ? invoices.map((invoice) => `
            <tr>
                <td>${escapeHtml(invoice.invoice_no || '-')}</td>
                <td style="font-size:12px;">${date(invoice.period_start)} - ${date(invoice.period_end)}</td>
                <td><strong>${money(invoice.net_amount)}</strong></td>
                <td><span class="badge-status badge-${escapeHtml(invoice.status || 'pending')}">${escapeHtml(invoice.status || 'pending')}</span></td>
            </tr>
        `).join('') : '<tr><td colspan="4" style="text-align:center;padding:20px;color:#94A3B8;">No invoices</td></tr>';
    }

    function renderHistory(data) {
        if (!data.farmer) {
            emptyState.style.display = '';
            historyContent.style.display = 'none';
            profileLink.style.display = 'none';
            return;
        }

        emptyState.style.display = 'none';
        historyContent.style.display = '';
        profileLink.href = `/farmers/${data.farmer.id}/edit`;
        profileLink.style.display = '';

        document.getElementById('kpi-total-collection').textContent = `${Number(data.total_collection || 0).toLocaleString('en-IN')} L`;
        document.getElementById('kpi-this-month').textContent = `${Number(data.this_month || 0).toFixed(1)} L`;
        document.getElementById('kpi-pending-payment').textContent = money(data.pending_payment);
        document.getElementById('kpi-avg-daily').textContent = `${Number(data.avg_daily || 0).toFixed(1)} L`;

        renderCollections(data.collection_history || []);
        renderInvoices(data.invoice_history || []);
    }

    async function loadHistory(farmerId = params.get('farmer_id') || '') {
        try {
            const query = new URLSearchParams();
            if (farmerId) query.set('farmer_id', farmerId);

            const response = await DairyApp.api(`/api/farmer-history?${query.toString()}`);
            const data = response.data || response;

            renderFarmers(data.farmers || [], farmerId);
            renderHistory(data);
        } catch (error) {
            DairyApp.flash(error.message, 'error');
        }
    }

    farmerSelect.addEventListener('change', () => {
        params.delete('farmer_id');
        loadHistory(farmerSelect.value);
    });

    loadHistory();
});
</script>
@endpush
