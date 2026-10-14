@extends('layouts.app')
@section('title', 'Milk Rate Management')
@section('page-title', 'Milk Rate Management')

@section('content')
<div id="rate-action-message"></div>

<form id="milk-rates-form">
    @csrf
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Rate Slabs</h3>
            <button type="button" class="btn btn-outline" id="add-rate-row"><i class="fas fa-plus"></i> Add Rate</button>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Milk Type</th>
                        <th>Fat Min</th>
                        <th>Fat Max</th>
                        <th>SNF Min</th>
                        <th>SNF Max</th>
                        <th>Rate/L</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="rate-rows-body">
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:#94A3B8;">Loading milk rates...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Rates</button>
    </div>
</form>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3 class="card-title">Import Excel Rate Chart</h3>
    </div>
    <form id="rate-import-form" style="padding:0 0 4px;">
        @csrf
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Milk Type</label>
                <select name="milk_type" class="form-input" required>
                    <option value="cow">Cow Milk</option>
                    <option value="buffalo">Buffalo Milk</option>
                    <option value="a2">A2 Premium</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Rate Chart File</label>
                <input type="file" name="rate_chart" class="form-input" accept=".xlsx,.xls,.csv" required>
            </div>
        </div>
        <button type="submit" class="btn btn-outline"><i class="fas fa-file-import"></i> Import Chart</button>
    </form>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3 class="card-title">Rate Change History</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Milk Type</th>
                    <th>Old Rate</th>
                    <th>New Rate</th>
                    <th>Changed By</th>
                </tr>
            </thead>
            <tbody id="rate-history-body">
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8;">Loading history...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('milk-rates-form');
    const importForm = document.getElementById('rate-import-form');
    const rateRowsBody = document.getElementById('rate-rows-body');
    const addRateRowButton = document.getElementById('add-rate-row');
    const historyBody = document.getElementById('rate-history-body');
    const actionMessage = document.getElementById('rate-action-message');
    const milkLabels = { cow: 'Cow Milk', buffalo: 'Buffalo Milk', a2: 'A2 Premium' };
    let rowIndex = 0;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const milkValue = (rate) => rate.milk_type?.value || rate.milk_type;
    const money = (value) => 'Rs. ' + Number(value || 0).toFixed(2);
    const formatDate = (value) => value
        ? new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
        : '-';

    function showMessage(message, type = 'success') {
        actionMessage.innerHTML = `
            <div class="alert alert-${type === 'error' ? 'danger' : type}" style="margin-bottom:16px;">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                ${escapeHtml(message)}
            </div>
        `;
        actionMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.clearTimeout(showMessage.timer);
        showMessage.timer = window.setTimeout(() => {
            actionMessage.innerHTML = '';
        }, 5000);
    }

    function rateRow(rate = {}) {
        const index = rowIndex++;
        const type = milkValue(rate) || 'cow';

        return `
            <tr data-rate-row>
                <td>
                    <input type="hidden" name="rates[${index}][id]" value="${escapeHtml(rate.id || '')}">
                    <select name="rates[${index}][milk_type]" class="form-input" required>
                        ${Object.entries(milkLabels).map(([value, label]) => `
                            <option value="${value}" ${type === value ? 'selected' : ''}>${label}</option>
                        `).join('')}
                    </select>
                </td>
                <td><input type="number" step="0.1" min="0" max="15" name="rates[${index}][fat_min]" class="form-input" value="${Number(rate.fat_min ?? 0)}" required></td>
                <td><input type="number" step="0.1" min="0" max="15" name="rates[${index}][fat_max]" class="form-input" value="${Number(rate.fat_max ?? 0)}" required></td>
                <td><input type="number" step="0.1" min="0" max="15" name="rates[${index}][snf_min]" class="form-input" value="${Number(rate.snf_min ?? 0)}" required></td>
                <td><input type="number" step="0.1" min="0" max="15" name="rates[${index}][snf_max]" class="form-input" value="${Number(rate.snf_max ?? 0)}" required></td>
                <td><input type="number" step="0.01" min="0" name="rates[${index}][rate_per_litre]" class="form-input" value="${Number(rate.rate_per_litre ?? 0)}" required></td>
                <td><button type="button" class="btn-icon" title="Remove Rate" data-remove-rate><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    }

    function renderRates(rates) {
        rowIndex = 0;
        rateRowsBody.innerHTML = rates.length ? rates.map((rate) => rateRow(rate)).join('') : rateRow();
    }

    function renderHistory(history) {
        historyBody.innerHTML = history.length ? history.map((item) => {
            const type = item.milk_type?.value || item.milk_type;
            const changedBy = item.changed_by?.name || item.changedBy?.name || item.changed_by_name || 'System';
            return `<tr><td>${formatDate(item.created_at)}</td><td><span class="milk-type-badge milk-type-${escapeHtml(type)}">${escapeHtml(milkLabels[type] || type || '-')}</span></td><td>${money(item.old_rate)}</td><td style="color:#10B981;font-weight:700;">${money(item.new_rate)}</td><td>${escapeHtml(changedBy)}</td></tr>`;
        }).join('') : '<tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8;">No rate changes recorded</td></tr>';
    }

    async function loadRates() {
        try {
            const response = await DairyApp.api('/api/milk-rates');
            const payload = response.data || response;
            renderRates(payload.rates || []);
            renderHistory(payload.history || []);
        } catch (error) {
            rateRowsBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#DC2626;">Unable to load milk rates</td></tr>';
            historyBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#DC2626;">Unable to load history</td></tr>';
            showMessage(error.message, 'error');
        }
    }

    addRateRowButton.addEventListener('click', () => {
        rateRowsBody.insertAdjacentHTML('afterbegin', rateRow());
        const firstInput = rateRowsBody.querySelector('[data-rate-row] select, [data-rate-row] input:not([type="hidden"])');
        firstInput?.focus();
        showMessage('New rate row added at the top.');
    });

    rateRowsBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-rate]');
        if (!button) return;
        button.closest('[data-rate-row]').remove();
        showMessage('Rate row removed. Click Update Rates to save this change.');
        if (!rateRowsBody.querySelector('[data-rate-row]')) {
            rateRowsBody.insertAdjacentHTML('afterbegin', rateRow());
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const result = await DairyApp.api('/api/milk-rates', { method: 'PUT', body: JSON.stringify(DairyApp.formPayload(form)) });
            showMessage(result.message || 'Rates updated successfully.');
            await loadRates();
        } catch (error) {
            showMessage(error.message, 'error');
        }
    });

    importForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const result = await DairyApp.api('/api/milk-rates/import', {
                method: 'POST',
                body: new FormData(importForm),
            });

            showMessage(result.message || 'Rate chart imported successfully.');
            importForm.reset();
            await loadRates();
        } catch (error) {
            showMessage(error.message, 'error');
        }
    });

    loadRates();
});
</script>
@endpush
