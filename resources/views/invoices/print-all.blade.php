<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="api-base-url" content="{{ config('app.api_base_url') }}">
    <title>All Invoices</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 11px; color: #1E293B; padding: 20px; }
        .page-title { border-bottom: 3px solid #10B981; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; }
        .page-title h1 { font-size: 20px; color: #0F172A; }
        .inv { padding: 16px; border: 1px solid #E2E8F0; margin-bottom: 14px; page-break-inside: avoid; }
        .inv h2 { font-size: 16px; color: #0F172A; margin-bottom: 8px; }
        .inv .row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .inv .net { font-size: 14px; font-weight: 800; color: #10B981; }
        .meta { font-size: 10px; color: #64748B; }
        .loading { text-align: center; color: #64748B; padding: 60px; }
        .error { text-align: center; color: #DC2626; padding: 60px; }
        @media print { body { padding: 12px; } }
    </style>
</head>
<body>
    <div id="print-root" class="loading">Loading invoices...</div>

    <script>
    const API_BASE = document.querySelector('meta[name="api-base-url"]').content.replace(/\/+$/, '');
    const TOKEN = localStorage.getItem('dairy_api_token');

    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN');
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
        if (!response.ok) throw new Error(data.message || 'Unable to load invoices.');
        return data;
    }

    function render(invoices, cycle) {
        document.getElementById('print-root').className = '';
        document.getElementById('print-root').innerHTML = `
            <div class="page-title">
                <div>
                    <h1>JAY MALHAR DUDH DAIRY JORI LAVAN</h1>
                    <div class="meta">Mr. Bibhishan Jijabhau Auti</div>
                    <div class="meta">Ap- Jori Lavan (Jambut), Tal- Shirur, Dist- Pune</div>
                    <div class="meta">Pune, Maharashtra-410504</div>
                    <div class="meta">All Invoices${cycle ? ` - ${escapeHtml(cycle.name || '')}` : ''}</div>
                </div>
                <div class="meta">Generated: ${date(new Date())}</div>
            </div>
            ${invoices.length ? invoices.map((invoice) => `
                <div class="inv">
                    <h2>${escapeHtml(invoice.invoice_no || '-')} - ${escapeHtml(invoice.farmer?.name || '-')}</h2>
                    <div class="row">
                        <span>${date(invoice.period_start)} - ${date(invoice.period_end)}</span>
                        <span class="net">Net: ${money(invoice.net_amount)}</span>
                    </div>
                    <div class="meta">
                        Farmer ID: ${escapeHtml(invoice.farmer?.farmer_id || '-')} |
                        Litres: ${Number(invoice.total_litres || 0).toLocaleString('en-IN')} |
                        Gross: ${money(invoice.gross_amount)} |
                        Deductions: ${money(invoice.total_deductions)} |
                        Status: ${escapeHtml(invoice.status || '-')}
                    </div>
                </div>
            `).join('') : '<div class="loading">No invoices found for this cycle.</div>'}
        `;
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const params = new URLSearchParams(window.location.search);
        const cycleId = params.get('bill_cycle_id');
        const query = new URLSearchParams();
        if (cycleId) query.set('bill_cycle_id', cycleId);
        query.set('per_page', '500');

        try {
            const response = await api(`/invoices?${query.toString()}`);
            const payload = response.data || response;
            const page = payload.invoices?.data ? payload.invoices : payload.invoices || { data: [] };
            const cycle = (payload.cycles || []).find((item) => String(item.id) === String(cycleId)) || payload.current_cycle;

            render(page.data || [], cycle);
            setTimeout(() => window.print(), 300);
        } catch (error) {
            document.getElementById('print-root').className = 'error';
            document.getElementById('print-root').textContent = error.message;
        }
    });
    </script>
</body>
</html>
