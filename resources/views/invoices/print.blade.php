<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="api-base-url" content="{{ config('app.api_base_url') }}">
    <title>Invoice Print</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'DM Sans', Arial, sans-serif; font-size: 12px; color: #1E293B; padding: 30px; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #10B981; padding-bottom: 16px; margin-bottom: 16px; }
        .invoice-header h1 { font-size: 22px; color: #0F172A; }
        .invoice-header .sub { font-size: 11px; color: #64748B; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
        .info-item { font-size: 12px; }
        .info-item label { color: #64748B; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 11px; }
        th { background: #F8FAFC; padding: 8px 10px; text-align: left; font-weight: 600; color: #64748B; border-bottom: 2px solid #E2E8F0; }
        td { padding: 7px 10px; border-bottom: 1px solid #E2E8F0; }
        .totals { text-align: right; }
        .totals td { border: none; padding: 4px 10px; }
        .net-total { font-size: 16px; font-weight: 800; color: #10B981; border-top: 2px solid #0F172A; padding-top: 8px !important; }
        .footer { margin-top: 24px; text-align: center; font-size: 10px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 12px; }
        .loading { text-align: center; color: #64748B; padding: 60px; }
        .error { text-align: center; color: #DC2626; padding: 60px; }
        @media print { body { padding: 18px; } }
    </style>
</head>
<body>
    <div id="print-root" class="loading">Loading invoice...</div>

    <script>
    const API_BASE = document.querySelector('meta[name="api-base-url"]').content.replace(/\/+$/, '');
    const TOKEN = localStorage.getItem('dairy_api_token');

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const date = (value) => value ? new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    async function api(path) {
        const response = await fetch(`${API_BASE}/${path.replace(/^\/+/, '')}`, {
            credentials: 'omit',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(TOKEN ? { 'Authorization': `Bearer ${TOKEN}` } : {}),
            },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Unable to load invoice.');
        return data;
    }

    function render(invoice, collections, deductions) {
        document.title = `Invoice ${invoice.invoice_no || invoice.id}`;
        document.getElementById('print-root').className = '';
        document.getElementById('print-root').innerHTML = `
            <div class="invoice-header">
                <div>
                    <h1>JAY MALHAR DUDH DAIRY JORI LAVAN</h1>
                    <div class="sub">Mr. Bibhishan Jijabhau Auti</div>
                    <div class="sub">Ap- Jori Lavan (Jambut), Tal- Shirur, Dist- Pune</div>
                    <div class="sub">Pune, Maharashtra-410504</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:14px;font-weight:700;">INVOICE</div>
                    <div>${escapeHtml(invoice.invoice_no || '-')}</div>
                    <div style="font-size:11px;color:#64748B;">Generated: ${date(new Date())}</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item"><label>Farmer:</label> ${escapeHtml(invoice.farmer?.name || '-')}</div>
                <div class="info-item"><label>ID:</label> ${escapeHtml(invoice.farmer?.farmer_id || '-')}</div>
                <div class="info-item"><label>Phone:</label> ${escapeHtml(invoice.farmer?.phone || '-')}</div>
                <div class="info-item"><label>Village:</label> ${escapeHtml(invoice.farmer?.village || '-')}</div>
                <div class="info-item"><label>Period:</label> ${date(invoice.period_start)} - ${date(invoice.period_end)}</div>
                <div class="info-item"><label>Status:</label> ${escapeHtml(String(invoice.status || '').toUpperCase())}</div>
            </div>

            <table>
                <thead>
                    <tr><th>#</th><th>Date</th><th>Session</th><th>Fat</th><th>SNF</th><th>Qty (L)</th><th>Rate (₹)</th><th>Amount (₹)</th></tr>
                </thead>
                <tbody>
                    ${collections.length ? collections.map((collection, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${date(collection.collection_date)}</td>
                            <td>${escapeHtml(collection.session || '-')}</td>
                            <td>${Number(collection.fat_percent || 0).toFixed(1)}</td>
                            <td>${Number(collection.snf_percent || 0).toFixed(1)}</td>
                            <td>${Number(collection.quantity || 0).toFixed(1)}</td>
                            <td>${money(collection.rate_per_litre)}</td>
                            <td>${money(collection.amount)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="8" style="text-align:center;color:#94A3B8;">No collections</td></tr>'}
                </tbody>
            </table>

            ${deductions.length ? `
                <table>
                    <thead><tr><th colspan="2">Deductions</th></tr></thead>
                    <tbody>
                        ${deductions.map((deduction) => `
                            <tr>
                                <td>${escapeHtml(deduction.deduction_type || '-')}</td>
                                <td style="text-align:right;color:#EF4444;">- ${money(deduction.amount)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            ` : ''}

            <table class="totals">
                <tr><td>Gross Amount:</td><td>${money(invoice.gross_amount)}</td></tr>
                <tr><td>Total Deductions:</td><td style="color:#EF4444;">- ${money(invoice.total_deductions)}</td></tr>
                <tr><td class="net-total">Net Payable:</td><td class="net-total">${money(invoice.net_amount)}</td></tr>
            </table>

            <div class="footer">
                JAY MALHAR DUDH DAIRY JORI LAVAN | Generated on ${date(new Date())}
            </div>
        `;
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const id = window.location.pathname.match(/\/invoices\/(\d+)\/print/)?.[1];
        if (!id) {
            document.getElementById('print-root').className = 'error';
            document.getElementById('print-root').textContent = 'Invoice id missing.';
            return;
        }

        try {
            const response = await api(`/invoices/${id}`);
            const payload = response.data || response;
            render(payload.invoice, payload.collections || [], payload.deductions || []);
            setTimeout(() => window.print(), 300);
        } catch (error) {
            document.getElementById('print-root').className = 'error';
            document.getElementById('print-root').textContent = error.message;
        }
    });
    </script>
</body>
</html>
