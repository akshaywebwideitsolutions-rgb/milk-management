@extends('layouts.app')
@section('title', 'Bill Cycle Management')
@section('page-title', 'Bill Cycle Settings')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Cycle Configuration</h3></div>
        <form id="cycle-settings-form" data-cycle-api-action="/api/bill-cycles/settings" data-api-method="PATCH">
            @csrf
            <div class="form-group">
                <label class="form-label">Cycle Type</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="cycle_type" value="10_days">
                        <span>10 Days</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="cycle_type" value="15_days" checked>
                        <span>15 Days</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="cycle_type" value="custom">
                        <span>Custom</span>
                    </label>
                </div>
            </div>

            <div class="toggle-group">
                <label class="toggle-label">
                    <span>Auto-Generate Invoice</span>
                    <div class="toggle-switch">
                        <input type="checkbox" name="auto_generate_invoice" value="1" checked>
                        <span class="toggle-slider"></span>
                    </div>
                </label>
                <label class="toggle-label">
                    <span>SMS on Invoice</span>
                    <div class="toggle-switch">
                        <input type="checkbox" name="sms_on_invoice" value="1" checked>
                        <span class="toggle-slider"></span>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Create New Cycle</h3></div>
        <div class="next-cycle-box" id="next-cycle-box" style="display:none;">
            <div style="font-size:12px;color:#065F46;font-weight:600;">Next Cycle Date</div>
            <div style="font-size:20px;font-weight:800;color:#065F46;" id="next-cycle-date">-</div>
        </div>

        <form id="create-cycle-form" data-cycle-api-action="/api/bill-cycles" style="margin-top:16px;">
            @csrf
            <div class="form-group">
                <label class="form-label">Cycle Type</label>
                <select name="cycle_type" class="form-input" required>
                    <option value="10_days">10 Days</option>
                    <option value="15_days" selected>15 Days</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-input" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fas fa-plus"></i> Create Cycle</button>
        </form>

        <div style="margin-top:24px;">
            <h4 style="font-size:14px;font-weight:600;margin-bottom:8px;">All Cycles</h4>
            <div class="cycle-list" id="cycle-list">
                <div style="padding:16px;color:#94A3B8;text-align:center;">Loading cycles...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cycleList = document.getElementById('cycle-list');
    const nextCycleBox = document.getElementById('next-cycle-box');
    const nextCycleDate = document.getElementById('next-cycle-date');
    const settingsForm = document.getElementById('cycle-settings-form');
    const createForm = document.getElementById('create-cycle-form');

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const formatDate = (value, options = { day: '2-digit', month: 'short', year: 'numeric' }) => {
        if (!value) return '-';
        return new Intl.DateTimeFormat('en-IN', options).format(new Date(value));
    };

    const nextDay = (value) => {
        const date = new Date(value);
        date.setDate(date.getDate() + 1);
        return date;
    };

    async function loadCycles() {
        cycleList.innerHTML = '<div style="padding:16px;color:#94A3B8;text-align:center;">Loading cycles...</div>';

        try {
            const response = await DairyApp.api('/api/bill-cycles');
            const cycles = response.data?.cycles || [];
            const currentCycle = response.data?.current_cycle;

            if (currentCycle) {
                settingsForm.querySelector(`[name="cycle_type"][value="${currentCycle.cycle_type}"]`)?.click();
                settingsForm.querySelector('[name="auto_generate_invoice"]').checked = Boolean(currentCycle.auto_generate_invoice);
                settingsForm.querySelector('[name="sms_on_invoice"]').checked = Boolean(currentCycle.sms_on_invoice);
                nextCycleDate.textContent = formatDate(nextDay(currentCycle.end_date));
                nextCycleBox.style.display = '';
            } else {
                nextCycleBox.style.display = 'none';
            }

            cycleList.innerHTML = cycles.length ? cycles.map((cycle) => {
                const status = cycle.status || 'pending';
                const badgeClass = status === 'active' || status === 'completed' ? 'badge-green' : 'badge-yellow';
                const activeClass = status === 'active' ? 'cycle-active' : '';
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);

                return `
                    <div class="cycle-item ${activeClass}">
                        <div>
                            <div style="font-weight:600;font-size:13px;">${escapeHtml(cycle.name || 'Bill Cycle')}</div>
                            <div style="font-size:12px;color:#64748B;">${formatDate(cycle.start_date, { day: '2-digit', month: 'short' })} - ${formatDate(cycle.end_date)}</div>
                        </div>
                        <span class="badge-status ${badgeClass}">${escapeHtml(statusText)}</span>
                    </div>
                `;
            }).join('') : '<div style="padding:16px;color:#94A3B8;text-align:center;">No cycles found</div>';
        } catch (error) {
            cycleList.innerHTML = '<div style="padding:16px;color:#DC2626;text-align:center;">Unable to load cycles</div>';
            DairyApp.flash(error.message, 'error');
        }
    }

    [settingsForm, createForm].forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            try {
                const result = await DairyApp.api(form.dataset.cycleApiAction, {
                    method: form.dataset.apiMethod || 'POST',
                    body: JSON.stringify(form === createForm ? { ...DairyApp.formPayload(form), auto_generate_invoice: settingsForm.querySelector('[name=auto_generate_invoice]').checked, sms_on_invoice: settingsForm.querySelector('[name=sms_on_invoice]').checked } : DairyApp.formPayload(form)),
                });

                DairyApp.flash(result.message || 'Saved successfully.');
                if (form === createForm) form.reset();
                await loadCycles();
            } catch (error) {
                DairyApp.flash(error.message, 'error');
            }
        });
    });

    loadCycles();
});
</script>
@endpush
