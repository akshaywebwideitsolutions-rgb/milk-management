@extends('layouts.app')
@section('title', 'Milk Collection')
@section('page-title', 'Milk Collection')

@push('styles')
<style>
    .collection-split { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; }
    .today-total { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-top: 16px; }
    .today-total .label { font-size: 13px; color: #065F46; font-weight: 600; }
    .today-total .value { font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 800; color: #065F46; }
</style>
@endpush

@section('content')
<div class="page-header-bar">
    <div style="display:flex;align-items:center;gap:12px;">
        <form method="GET" action="{{ route('milk-collection.index') }}" style="display:flex;gap:8px;">
            <input type="date" name="date" class="form-input" id="filter-date" style="width:170px;">
            <select name="session" class="form-input" id="filter-session" style="width:130px;">
                <option value="morning">Morning</option>
                <option value="evening">Evening</option>
            </select>
            <button type="submit" class="btn btn-outline"><i class="fas fa-sync-alt"></i></button>
        </form>
    </div>
</div>

<div class="collection-split" style="margin-top:16px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Entry</h3>
        </div>
        <form id="collection-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Search Farmer ID / Name</label>
                <input type="text" class="form-input" id="farmer-search" placeholder="Type farmer ID or name...">
            </div>
            <div class="form-group">
                <label class="form-label">Select Farmer *</label>
                <select name="farmer_id" class="form-input" required id="farmer-select">
                    <option value="">- Choose Farmer -</option>
                </select>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Fat %</label>
                    <input type="number" step="0.1" min="0" name="fat_percent" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">SNF %</label>
                    <input type="number" step="0.1" min="0" name="snf_percent" class="form-input">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Quantity (Litres) *</label>
                    <input type="number" step="0.1" min="0.1" name="quantity" class="form-input" id="qty-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Rate (₹/L)</label>
                    <input type="text" name="rate_display" class="form-input" id="rate-display" value="₹0.00" readonly style="background:#F8FAFC;">
                    <input type="hidden" name="collection_date" id="collection-date">
                    <input type="hidden" name="session" id="collection-session">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Amount</label>
                <div class="auto-amount" id="amount-display">₹0.00</div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;margin-top:4px;">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#64748B;cursor:pointer;">
                    <input type="checkbox" name="send_sms" value="1" checked> Send SMS to farmer
                </label>
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary" name="send_sms" value="1"><i class="fas fa-paper-plane"></i> Save Entry &amp; Send SMS</button>
                <button type="submit" class="btn btn-outline" name="send_sms" value="0" formnovalidate>
                    <i class="fas fa-save"></i> Save Only
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Today's Entries</h3>
            <span class="card-subtitle" id="entries-subtitle">-</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Farmer</th>
                        <th>Qty(L)</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="collection-entries-body">
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:#94A3B8;">Loading entries...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="today-total">
            <div><span class="label">Total</span></div>
            <div style="display:flex;gap:24px;">
                <div><span class="label">Quantity: </span><span class="value" id="collection-total-quantity">0.0 L</span></div>
                <div><span class="label">Amount: </span><span class="value" id="collection-total-amount">₹0</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    const today = new Date().toISOString().slice(0, 10);
    const date = params.get('date') || today;
    const session = params.get('session') || 'morning';
    const form = document.getElementById('collection-form');
    const farmerSelect = document.getElementById('farmer-select');
    const farmerSearch = document.getElementById('farmer-search');
    const entriesBody = document.getElementById('collection-entries-body');
    const qtyInput = document.getElementById('qty-input');
    const fatInput = form.querySelector('[name="fat_percent"]');
    const snfInput = form.querySelector('[name="snf_percent"]');
    const rateDisplay = document.getElementById('rate-display');
    const amountDisplay = document.getElementById('amount-display');
    let allFarmers = [];

    document.getElementById('filter-date').value = date;
    document.getElementById('filter-session').value = session;
    document.getElementById('collection-date').value = date;
    document.getElementById('collection-session').value = session;
    document.getElementById('entries-subtitle').textContent = `${date} - ${session.charAt(0).toUpperCase() + session.slice(1)}`;

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    function calcAmount() {
        const qty = parseFloat(qtyInput.value) || 0;
        const rate = parseFloat(rateDisplay.value.replace(/[₹,]/g, '')) || 0;
        amountDisplay.textContent = money(qty * rate);
    }

    function renderFarmerOptions(farmers) {
        farmerSelect.innerHTML = '<option value="">- Choose Farmer -</option>' + farmers.map((farmer) => {
            const milkType = farmer.milk_type?.value || farmer.milk_type;
            return `<option value="${farmer.id}" data-milk-type="${milkType}">${farmer.name} - ${farmer.farmer_id}</option>`;
        }).join('');
    }

    function selectFarmer(farmer) {
        if (!farmer) return;
        farmerSelect.value = farmer.id;
        farmerSelect.dispatchEvent(new Event('change'));
    }

    function filterFarmers() {
        const query = farmerSearch.value.trim().toLowerCase();

        if (!query) {
            renderFarmerOptions(allFarmers);
            return;
        }

        const matches = allFarmers.filter((farmer) => {
            const id = String(farmer.farmer_id || '').toLowerCase();
            const name = String(farmer.name || '').toLowerCase();
            const phone = String(farmer.phone || '').toLowerCase();
            return id.includes(query) || name.includes(query) || phone.includes(query);
        });
        const exact = matches.find((farmer) =>
            String(farmer.farmer_id || '').toLowerCase() === query ||
            String(farmer.name || '').toLowerCase() === query
        );

        renderFarmerOptions(matches);

        if (exact) {
            selectFarmer(exact);
        } else if (matches.length === 1) {
            selectFarmer(matches[0]);
        }
    }

    function printReceipt(entry) {
        const farmer = entry.farmer || {};
        const receiptWindow = window.open('', '_blank', 'width=420,height=720');

        if (!receiptWindow) {
            DairyApp.flash('Popup blocked. Please allow popups to print receipt.', 'error');
            return;
        }

        receiptWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>KOT Milk Receipt</title>
                <style>
                    * { box-sizing: border-box; }
                    @page { size: 80mm auto; margin: 4mm; }
                    body { font-family: Arial, sans-serif; margin: 0; padding: 16px; color: #000; font-size: 12px; background: #fff; }
                    .toolbar { width: 76mm; max-width: 100%; margin: 0 auto 10px; display: flex; gap: 8px; justify-content: flex-end; }
                    .toolbar button { border: 1px solid #999; border-radius: 4px; padding: 7px 12px; font-weight: 700; cursor: pointer; background: #fff; color: #000; }
                    .receipt { width: 76mm; margin: 0 auto; padding: 4px 2px; background: #fff; }
                    .center { text-align: center; }
                    .shop { font-size: 16px; font-weight: 800; line-height: 1.2; text-transform: uppercase; }
                    .address { font-size: 10px; line-height: 1.35; margin-top: 4px; }
                    .line { border-top: 1px dashed #000; margin: 9px 0; }
                    .row { display: flex; justify-content: space-between; gap: 10px; padding: 4px 0; }
                    .row span:first-child { white-space: nowrap; font-weight: 700; }
                    .row strong { text-align: right; overflow-wrap: anywhere; }
                    .total { border-top: 1px dashed #000; border-bottom: 1px dashed #000; margin-top: 8px; padding: 7px 0; font-size: 14px; font-weight: 900; }
                    .footer { text-align: center; margin-top: 10px; font-size: 10px; line-height: 1.4; }
                    @media print {
                        body { width: 80mm; padding: 0; background: #fff; }
                        .toolbar { display: none; }
                        .receipt { width: 72mm; }
                    }
                </style>
            </head>
            <body>
                <div class="toolbar">
                    <button type="button" class="print" onclick="window.print()">Print</button>
                    <button type="button" class="close" onclick="window.close()">Close</button>
                </div>
                <div class="receipt">
                    <div class="center">
                        <div class="shop">Jay Malhar Dudh Dairy Jori Lavan</div>
                        <div class="address">
                            Mr. Bibhishan Jijabhau Auti<br>
                            Ap- Jori Lavan (Jambut), Tal- Shirur, Dist- Pune<br>
                            Pune, Maharashtra-410504
                        </div>
                    </div>
                    <div class="line"></div>
                    <div class="row"><span>Date</span><strong>${escapeHtml(entry.collection_date || '-')}</strong></div>
                    <div class="row"><span>Session</span><strong>${escapeHtml(entry.session || '-')}</strong></div>
                    <div class="row"><span>Farmer</span><strong>${escapeHtml(farmer.name || '-')}</strong></div>
                    <div class="row"><span>Farmer ID</span><strong>${escapeHtml(farmer.farmer_id || '-')}</strong></div>
                    <div class="row"><span>Milk Type</span><strong>${escapeHtml(entry.milk_type || '-')}</strong></div>
                    <div class="row"><span>Fat</span><strong>${Number(entry.fat_percent || 0).toFixed(1)}%</strong></div>
                    <div class="row"><span>SNF</span><strong>${Number(entry.snf_percent || 0).toFixed(1)}%</strong></div>
                    <div class="row"><span>Quantity</span><strong>${Number(entry.quantity || 0).toFixed(1)} L</strong></div>
                    <div class="row"><span>Rate</span><strong>${money(entry.rate_per_litre)}</strong></div>
                    <div class="row total"><span>AMOUNT</span><strong>${money(entry.amount)}</strong></div>
                    <div class="footer">
                        ${new Date().toLocaleString('en-IN')}<br>
                        Thank You
                    </div>
                </div>
                <script>
                    window.onload = () => {
                        window.focus();
                    };
                <\/script>
            </body>
            </html>
        `);
        receiptWindow.document.close();
    }

    async function loadCollection() {
        try {
            const response = await DairyApp.api(`/api/milk-collection?date=${date}&session=${session}`);
            const { farmers, entries, totals } = response.data;

            allFarmers = farmers;
            filterFarmers();

            entriesBody.innerHTML = entries.length
                ? entries.map((entry) => `
                    <tr>
                        <td>${entry.farmer?.name || '-'}</td>
                        <td>${Number(entry.quantity || 0).toFixed(1)}</td>
                        <td>${money(entry.rate_per_litre)}</td>
                        <td><strong>${money(entry.amount)}</strong></td>
                        <td>
                            <button type="button" class="btn-icon" title="Print Receipt" data-print-entry="${entry.id}">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8;">No entries yet</td></tr>';

            document.getElementById('collection-total-quantity').textContent = `${Number(totals.quantity || 0).toFixed(1)} L`;
            document.getElementById('collection-total-amount').textContent = money(totals.amount);

            document.querySelectorAll('[data-print-entry]').forEach((button) => {
                button.addEventListener('click', () => {
                    const entry = entries.find((item) => String(item.id) === String(button.dataset.printEntry));
                    if (entry) printReceipt(entry);
                });
            });
        } catch (error) {
            DairyApp.flash(error.message, 'error');
        }
    }

    function refreshRate() {
        const opt = farmerSelect.options[farmerSelect.selectedIndex];
        if (!opt.value) return;

        const query = new URLSearchParams({
            farmer_id: opt.value,
            fat_percent: fatInput.value || 0,
            snf_percent: snfInput.value || 0,
        });

        DairyApp.api('/api/milk-collection/get-rate?' + query.toString())
            .then((data) => {
                rateDisplay.value = money(data.data.rate);
                calcAmount();
            })
            .catch((error) => DairyApp.flash(error.message, 'error'));
    }

    farmerSelect.addEventListener('change', refreshRate);

    farmerSearch.addEventListener('input', filterFarmers);
    qtyInput.addEventListener('input', calcAmount);
    fatInput.addEventListener('input', refreshRate);
    snfInput.addEventListener('input', refreshRate);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const payload = DairyApp.formPayload(form);
            payload.collection_date = date;
            payload.session = session;
            payload.send_sms = event.submitter?.name === 'send_sms'
                ? event.submitter.value === '1'
                : Boolean(payload.send_sms);

            const result = await DairyApp.api('/api/milk-collection', {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            DairyApp.flash(result.message || 'Milk collection entry saved.');
            printReceipt(result.data);
            form.reset();
            farmerSearch.value = '';
            renderFarmerOptions(allFarmers);
            document.getElementById('collection-date').value = date;
            document.getElementById('collection-session').value = session;
            rateDisplay.value = '₹0.00';
            amountDisplay.textContent = '₹0.00';
            await loadCollection();
        } catch (error) {
            DairyApp.flash(error.message, 'error');
        }
    });

    await loadCollection();
});
</script>
@endpush
