@extends('layouts.app')
@section('title', 'Deduction Management')
@section('page-title', 'Deduction Management')

@section('content')
<div class="page-header-bar">
    <div style="display:flex;align-items:center;gap:12px;">
        <select class="form-input" style="width:220px;" id="cycle-filter">
            <option value="">All Cycles</option>
        </select>
        <div class="total-deductions-box">
            Total Deductions This Cycle: <strong id="total-deductions">₹0</strong>
        </div>
    </div>
    <button class="btn btn-primary" type="button" id="show-add-form"><i class="fas fa-plus"></i> Add Deduction</button>
</div>

<div class="card" id="add-form" style="display:none;margin-top:16px;border:2px solid #10B981;">
    <div class="card-header">
        <h3 class="card-title">New Deduction</h3>
        <button class="btn-icon" type="button" id="hide-add-form" style="background:none;border:none;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    <form id="deduction-form">
        @csrf
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Farmer *</label>
                <select name="farmer_id" class="form-input" required id="farmer-select">
                    <option value="">- Select -</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Bill Cycle *</label>
                <select name="bill_cycle_id" class="form-input" required id="cycle-select"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Deduction Type *</label>
                <select name="deduction_type" class="form-input" required>
                    <option>Feed Cost</option>
                    <option>Advance Payment</option>
                    <option>Loan EMI</option>
                    <option>Penalty</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Amount (₹) *</label>
                <input type="number" step="0.01" min="0" name="amount" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Date *</label>
                <input type="date" name="deduction_date" class="form-input" required value="{{ now()->toDateString() }}">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-input">
            </div>
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Deduction</button>
            <button type="button" class="btn btn-outline" id="cancel-add-form">Cancel</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top:16px;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Deduction Type</th>
                    <th>Farmer</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Cycle</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="deductions-table-body">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8;">Loading deductions...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="table-footer" id="deductions-table-footer">
        <span class="table-info">Loading deductions...</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cycleFilter = document.getElementById('cycle-filter');
    const cycleSelect = document.getElementById('cycle-select');
    const farmerSelect = document.getElementById('farmer-select');
    const totalDeductions = document.getElementById('total-deductions');
    const addForm = document.getElementById('add-form');
    const deductionForm = document.getElementById('deduction-form');
    const tbody = document.getElementById('deductions-table-body');
    const footer = document.getElementById('deductions-table-footer');
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

    function selectedCycleId(cycles) {
        return cycleFilter.value || params.get('bill_cycle_id') || cycles[0]?.id || '';
    }

    function renderSelects(cycles, farmers) {
        const selected = selectedCycleId(cycles);
        const cycleLabel = (cycle) => {
            const range = `${date(cycle.start_date)} - ${date(cycle.end_date)}`;
            return `${cycle.name || 'Bill Cycle'} (${range})`;
        };

        cycleFilter.innerHTML = '<option value="">All Cycles</option>' + cycles.map((cycle) => `
            <option value="${cycle.id}" ${String(cycle.id) === String(cycleFilter.value || params.get('bill_cycle_id')) ? 'selected' : ''}>${escapeHtml(cycleLabel(cycle))}</option>
        `).join('');

        cycleSelect.innerHTML = cycles.map((cycle) => `
            <option value="${cycle.id}" ${String(cycle.id) === String(selected) ? 'selected' : ''}>${escapeHtml(cycleLabel(cycle))}</option>
        `).join('');

        farmerSelect.innerHTML = '<option value="">- Select -</option>' + farmers.map((farmer) => `
            <option value="${farmer.id}">${escapeHtml(farmer.name)} (${escapeHtml(farmer.farmer_id)})</option>
        `).join('');
    }

    function renderDeductions(page) {
        const deductions = page.data || [];

        tbody.innerHTML = deductions.length ? deductions.map((deduction) => {
            const status = deduction.status || 'applied';
            const statusClass = status === 'applied' ? 'badge-green' : 'badge-yellow';

            return `
                <tr>
                    <td>${escapeHtml(deduction.deduction_type || '-')}</td>
                    <td>${escapeHtml(deduction.farmer?.name || '-')}</td>
                    <td style="font-weight:700;">${money(deduction.amount)}</td>
                    <td>${date(deduction.deduction_date)}</td>
                    <td>${escapeHtml(deduction.bill_cycle?.name || deduction.billCycle?.name || '-')}</td>
                    <td><span class="badge-status ${statusClass}">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</span></td>
                    <td>
                        <button type="button" class="btn-icon btn-icon-danger" title="Delete" data-delete-deduction="${deduction.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('') : '<tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8;">No deductions</td></tr>';

        footer.innerHTML = `<span class="table-info">Showing ${page.from || 0}-${page.to || 0} of ${page.total || 0}</span>`;

        document.querySelectorAll('[data-delete-deduction]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!confirm('Delete this deduction?')) return;

                try {
                    await DairyApp.api(`/api/deductions/${button.dataset.deleteDeduction}`, { method: 'DELETE' });
                    DairyApp.flash('Deduction deleted.');
                    await loadDeductions();
                } catch (error) {
                    DairyApp.flash(error.message, 'error');
                }
            });
        });
    }

    async function loadDeductions() {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8;">Loading deductions...</td></tr>';

        try {
            const query = new URLSearchParams();
            if (cycleFilter.value || params.get('bill_cycle_id')) {
                query.set('bill_cycle_id', cycleFilter.value || params.get('bill_cycle_id'));
            }

            const response = await DairyApp.api(`/api/deductions?${query.toString()}`);
            const payload = response.data || response;
            const page = payload.deductions?.data ? payload.deductions : payload.deductions || { data: [] };

            renderSelects(payload.cycles || [], payload.farmers || []);
            totalDeductions.textContent = money(payload.total_this_cycle);
            renderDeductions(page);
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#DC2626;">Unable to load deductions</td></tr>';
            footer.innerHTML = '<span class="table-info">Unable to load deductions</span>';
            DairyApp.flash(error.message, 'error');
        }
    }

    document.getElementById('show-add-form').addEventListener('click', () => {
        addForm.style.display = 'block';
    });

    [document.getElementById('hide-add-form'), document.getElementById('cancel-add-form')].forEach((button) => {
        button.addEventListener('click', () => {
            addForm.style.display = 'none';
        });
    });

    cycleFilter.addEventListener('change', () => {
        params.delete('bill_cycle_id');
        loadDeductions();
    });

    deductionForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const result = await DairyApp.api('/api/deductions', {
                method: 'POST',
                body: JSON.stringify(DairyApp.formPayload(deductionForm)),
            });

            DairyApp.flash(result.message || 'Deduction added.');
            deductionForm.reset();
            addForm.style.display = 'none';
            await loadDeductions();
        } catch (error) {
            DairyApp.flash(error.message, 'error');
        }
    });

    loadDeductions();
});
</script>
@endpush
