@extends('layouts.app')
@section('title', 'Invoice Generation')
@section('page-title', 'Invoice Generation')

@section('content')
<div class="page-header-bar">
    <div style="display:flex;align-items:center;gap:12px;">
        <div id="current-cycle-box" style="display:none;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:8px 16px;">
            <span style="font-size:12px;color:#065F46;font-weight:600;">Current Cycle: </span>
            <span style="font-size:13px;color:#065F46;" id="current-cycle-label"></span>
        </div>
        <select class="form-input" style="width:220px;" id="cycle-select">
            <option value="">All Cycles</option>
        </select>
    </div>
    <div style="display:flex;gap:8px;">
        <form id="generate-invoices-form">
            @csrf
            <input type="hidden" name="bill_cycle_id" id="generate-cycle-id">
            <button type="submit" class="btn btn-primary"><i class="fas fa-file-invoice-dollar"></i> Generate Invoices</button>
        </form>
        <a href="#" class="btn btn-outline" target="_blank" id="print-all-link" style="display:none;"><i class="fas fa-print"></i> Print All</a>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Farmer</th>
                    <th>Period</th>
                    <th>Total (L)</th>
                    <th>Gross Amount</th>
                    <th>Deductions</th>
                    <th>Net Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="invoices-table-body">
                <tr><td colspan="9" style="text-align:center;padding:40px;color:#94A3B8;">Loading invoices...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="table-footer" id="invoices-table-footer">
        <span class="table-info">Loading invoices...</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cycleSelect = document.getElementById('cycle-select');
    const currentCycleBox = document.getElementById('current-cycle-box');
    const currentCycleLabel = document.getElementById('current-cycle-label');
    const generateForm = document.getElementById('generate-invoices-form');
    const generateCycleId = document.getElementById('generate-cycle-id');
    const printAllLink = document.getElementById('print-all-link');
    const tbody = document.getElementById('invoices-table-body');
    const footer = document.getElementById('invoices-table-footer');
    const params = new URLSearchParams(window.location.search);

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN');
    const formatDate = (value, options = { day: '2-digit', month: 'short', year: 'numeric' }) => value
        ? new Intl.DateTimeFormat('en-IN', options).format(new Date(value))
        : '-';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    function selectedCycleId(currentCycle) {
        return cycleSelect.value || currentCycle?.id || '';
    }

    function syncPrintLink(cycleId) {
        if (!cycleId) {
            printAllLink.style.display = 'none';
            return;
        }

        printAllLink.href = `/invoices/print-all?bill_cycle_id=${encodeURIComponent(cycleId)}`;
        printAllLink.style.display = '';
    }

    function renderCycles(cycles, currentCycle) {
        const selected = params.get('bill_cycle_id') || currentCycle?.id || '';
        cycleSelect.innerHTML = '<option value="">All Cycles</option>' + cycles.map((cycle) => `
            <option value="${cycle.id}" ${String(cycle.id) === String(selected) ? 'selected' : ''}>${escapeHtml(cycle.name || 'Bill Cycle')}</option>
        `).join('');

        generateCycleId.value = selectedCycleId(currentCycle);
        syncPrintLink(generateCycleId.value);

        if (currentCycle) {
            currentCycleLabel.textContent = `${formatDate(currentCycle.start_date, { day: '2-digit', month: 'short' })} - ${formatDate(currentCycle.end_date)}`;
            currentCycleBox.style.display = '';
        } else {
            currentCycleBox.style.display = 'none';
        }
    }

    function renderInvoices(page) {
        const invoices = page.data || [];

        tbody.innerHTML = invoices.length ? invoices.map((invoice) => {
            const status = invoice.status || 'pending';

            return `
                <tr>
                    <td><strong>${escapeHtml(invoice.invoice_no || '-')}</strong></td>
                    <td>${escapeHtml(invoice.farmer?.name || '-')}</td>
                    <td>${formatDate(invoice.period_start, { day: '2-digit', month: 'short' })} - ${formatDate(invoice.period_end, { day: '2-digit', month: 'short' })}</td>
                    <td>${Number(invoice.total_litres || 0).toLocaleString('en-IN')}L</td>
                    <td>${money(invoice.gross_amount)}</td>
                    <td style="color:#EF4444;">- ${money(invoice.total_deductions)}</td>
                    <td><strong style="color:#10B981;">${money(invoice.net_amount)}</strong></td>
                    <td><span class="badge-status badge-${escapeHtml(status)}">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</span></td>
                    <td>
                        <a href="/invoices/${invoice.id}" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                        <a href="/invoices/${invoice.id}/print" class="btn-icon" title="Print" target="_blank"><i class="fas fa-print"></i></a>
                        <select data-invoice-status="${invoice.id}" class="form-input" style="width:90px;padding:4px 6px;font-size:11px;">
                            ${['pending', 'sent', 'paid'].map((option) => `<option value="${option}" ${status === option ? 'selected' : ''}>${option.charAt(0).toUpperCase() + option.slice(1)}</option>`).join('')}
                        </select>
                    </td>
                </tr>
            `;
        }).join('') : '<tr><td colspan="9" style="text-align:center;padding:40px;color:#94A3B8;">No invoices generated yet</td></tr>';

        footer.innerHTML = `<span class="table-info">Showing ${page.from || 0}-${page.to || 0} of ${page.total || 0}</span>`;

        document.querySelectorAll('[data-invoice-status]').forEach((select) => {
            select.addEventListener('change', async () => {
                try {
                    await DairyApp.api(`/api/invoices/${select.dataset.invoiceStatus}/status`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status: select.value }),
                    });
                    DairyApp.flash('Invoice status updated.');
                    await loadInvoices();
                } catch (error) {
                    DairyApp.flash(error.message, 'error');
                }
            });
        });
    }

    async function loadInvoices() {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:#94A3B8;">Loading invoices...</td></tr>';

        try {
            const query = new URLSearchParams();
            if (cycleSelect.value) query.set('bill_cycle_id', cycleSelect.value);

            const response = await DairyApp.api(`/api/invoices?${query.toString()}`);
            const payload = response.data || response;
            const page = payload.invoices?.data ? payload.invoices : payload.invoices || { data: [] };

            renderCycles(payload.cycles || [], payload.current_cycle);
            renderInvoices(page);
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:#DC2626;">Unable to load invoices</td></tr>';
            footer.innerHTML = '<span class="table-info">Unable to load invoices</span>';
            DairyApp.flash(error.message, 'error');
        }
    }

    cycleSelect.addEventListener('change', () => {
        generateCycleId.value = cycleSelect.value;
        syncPrintLink(cycleSelect.value);
        loadInvoices();
    });

    generateForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!generateCycleId.value) {
            DairyApp.flash('Please select an active bill cycle before generating invoices.', 'error');
            return;
        }

        if (!confirm('Generate invoices for all farmers in this cycle?')) return;

        try {
            const result = await DairyApp.api('/api/invoices/generate', {
                method: 'POST',
                body: JSON.stringify({ bill_cycle_id: generateCycleId.value }),
            });
            DairyApp.flash(result.message || 'Invoices generated.');
            await loadInvoices();
        } catch (error) {
            DairyApp.flash(error.message, 'error');
        }
    });

    loadInvoices();
});
</script>
@endpush
