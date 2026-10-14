<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-base-url" content="{{ config('app.api_base_url') }}">
    <title>@yield('title', 'Jay Malhar Dairy') — Dairy Management Application</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    @stack('styles')
</head>
<body>
    <div class="app-frame">
        {{-- Sidebar --}}
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon"><i class="fas fa-cow"></i></div>
                <div class="sidebar-logo-text">Jay Malhar Dairy</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main Menu</div>

                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="{{ route('farmers.index') }}" class="nav-item {{ request()->routeIs('farmers.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Farmer Mgmt
                </a>
                <a href="{{ route('milk-collection.index') }}" class="nav-item {{ request()->routeIs('milk-collection.*') ? 'active' : '' }}">
                    <i class="fas fa-tint"></i> Milk Collection
                </a>
                <a href="{{ route('milk-rates.index') }}" class="nav-item {{ request()->routeIs('milk-rates.*') ? 'active' : '' }}">
                    <i class="fas fa-rupee-sign"></i> Rate Mgmt
                </a>
                <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice"></i> Invoices
                </a>

                <div class="nav-label">Billing</div>

                <a href="{{ route('bill-cycles.index') }}" class="nav-item {{ request()->routeIs('bill-cycles.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i> Bill Cycle
                </a>
                <a href="{{ route('deductions.index') }}" class="nav-item {{ request()->routeIs('deductions.*') ? 'active' : '' }}">
                    <i class="fas fa-minus-circle"></i> Deductions
                </a>

                <div class="nav-label">Insights</div>

                <a href="{{ route('farmer-history.index') }}" class="nav-item {{ request()->routeIs('farmer-history.*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> History
                </a>
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>

                <div style="flex:1"></div>

                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <button type="button" class="nav-item" id="logout-button" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </nav>
        </aside>

        {{-- Main content --}}
        <div class="main-content">
            {{-- Top Bar --}}
            <header class="topbar">
                <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>
                <div class="topbar-actions">
                    <div style="position:relative">
                        <input type="text" class="form-input" placeholder="Search..." style="width:200px;padding-left:32px;">
                        <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:12px;"></i>
                    </div>
                    <div class="user-avatar" id="user-avatar" title="User">
                        <i class="fas fa-user-circle" style="font-size:28px;color:#0F172A;"></i>
                    </div>
                </div>
            </header>

            {{-- Content Area --}}
            <main class="content-area @yield('content-class', '')">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> {{ session('info') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="page-loader" id="page-loader"></div>

    <script>
    const DairyApp = {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        apiBase: document.querySelector('meta[name="api-base-url"]')?.content || '',
        tokenKey: 'dairy_api_token',
        userKey: 'dairy_api_user',
        cachePrefix: 'dairy_api_cache:',
        cacheTtl: 30000,

        token() {
            return localStorage.getItem(this.tokenKey);
        },

        setSession(token, user = null) {
            localStorage.setItem(this.tokenKey, token);
            if (user) localStorage.setItem(this.userKey, JSON.stringify(user));
        },

        clearSession() {
            localStorage.removeItem(this.tokenKey);
            localStorage.removeItem(this.userKey);
        },

        isLoginPage() {
            return window.location.pathname.replace(/\/+$/, '') === '/login';
        },

        requireToken() {
            if (!this.isLoginPage() && !this.token()) {
                window.location.href = '/login';
                return false;
            }

            return true;
        },

        apiUrl(path) {
            if (/^https?:\/\//i.test(path)) {
                const url = new URL(path);
                const apiIndex = url.pathname.indexOf('/api/');

                if (apiIndex !== -1 && url.origin === window.location.origin) {
                    return `${this.apiBase.replace(/\/+$/, '')}/${url.pathname.slice(apiIndex + 5)}${url.search}`;
                }

                return path;
            }

            const normalizedPath = path.replace(/^\/api\/?/, '').replace(/^\//, '');
            return `${this.apiBase.replace(/\/+$/, '')}/${normalizedPath}`;
        },

        async api(path, options = {}) {
            const token = this.token();
            const isFormData = options.body instanceof FormData;
            const isSameOriginApi = new URL(this.apiBase, window.location.origin).origin === window.location.origin;
            const method = (options.method || 'GET').toUpperCase();
            const url = this.apiUrl(path);
            const cacheKey = this.cachePrefix + url;

            if (method === 'GET') {
                const cached = sessionStorage.getItem(cacheKey);
                if (cached) {
                    try {
                        const entry = JSON.parse(cached);
                        if (Date.now() - entry.time < this.cacheTtl) {
                            return entry.data;
                        }
                    } catch (error) {
                        sessionStorage.removeItem(cacheKey);
                    }
                }
            }

            const headers = {
                'Accept': 'application/json',
                ...(!isFormData ? { 'Content-Type': 'application/json' } : {}),
                ...(isSameOriginApi ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...(options.headers || {}),
            };

            const response = await fetch(url, {
                credentials: 'omit',
                headers,
                ...options,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 401) {
                    this.clearSession();
                    if (!this.isLoginPage()) window.location.href = '/login';
                }

                const message = data.message || Object.values(data.errors || {}).flat()[0] || 'Request failed.';
                throw new Error(message);
            }

            if (method === 'GET') {
                sessionStorage.setItem(cacheKey, JSON.stringify({ time: Date.now(), data }));
            } else {
                Object.keys(sessionStorage)
                    .filter((key) => key.startsWith(this.cachePrefix))
                    .forEach((key) => sessionStorage.removeItem(key));
            }

            return data;
        },

        formPayload(form) {
            const payload = {};
            const formData = new FormData(form);

            for (const [key, value] of formData.entries()) {
                if (key === '_token' || key === '_method') continue;

                const arrayMatch = key.match(/^([^\[]+)\[(\d+)\]\[([^\]]+)\]$/);
                if (arrayMatch) {
                    const [, group, index, field] = arrayMatch;
                    payload[group] = payload[group] || [];
                    payload[group][index] = payload[group][index] || {};
                    payload[group][index][field] = value;
                    continue;
                }

                payload[key] = value;
            }

            form.querySelectorAll('input[type="checkbox"][name]').forEach((checkbox) => {
                payload[checkbox.name] = checkbox.checked;
            });

            return payload;
        },

        flash(message, type = 'success') {
            const area = document.querySelector('.content-area') || document.body;
            const alert = document.createElement('div');
            alert.className = `alert alert-${type === 'error' ? 'danger' : type}`;
            alert.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${message}`;
            area.prepend(alert);
            setTimeout(() => alert.remove(), 5000);
        },

        showPageLoader() {
            document.body.classList.add('is-navigating');
        },

        bindApiForms() {
            document.querySelectorAll('form[data-api-action]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitter = event.submitter;
                    const method = submitter?.dataset.apiMethod || form.dataset.apiMethod || 'POST';
                    const redirect = submitter?.dataset.redirect || form.dataset.redirect;

                    try {
                        const result = await this.api(form.dataset.apiAction, {
                            method,
                            body: JSON.stringify(this.formPayload(form)),
                        });

                        this.flash(result.message || 'Saved successfully.');

                        if (redirect) {
                            window.location.href = redirect;
                        } else if (form.dataset.reload === 'true') {
                            window.location.reload();
                        }
                    } catch (error) {
                        this.flash(error.message, 'error');
                    }
                });
            });
        },
    };

    window.DairyApp = DairyApp;

    document.addEventListener('DOMContentLoaded', () => {
        DairyApp.requireToken();
        DairyApp.bindApiForms();

        document.querySelectorAll('a.nav-item[href]').forEach((link) => {
            link.addEventListener('mouseenter', () => {
                if (link.dataset.prefetched || link.origin !== window.location.origin) return;

                const prefetch = document.createElement('link');
                prefetch.rel = 'prefetch';
                prefetch.href = link.href;
                document.head.appendChild(prefetch);
                link.dataset.prefetched = 'true';
            }, { once: true });

            link.addEventListener('click', () => {
                if (link.href !== window.location.href) {
                    DairyApp.showPageLoader();
                }
            });
        });

        const user = JSON.parse(localStorage.getItem(DairyApp.userKey) || 'null');
        const avatar = document.getElementById('user-avatar');
        const logoutButton = document.getElementById('logout-button');
        const qtyInput = document.getElementById('qty-input');
        const rateDisplay = document.getElementById('rate-display');
        const amountDisplay = document.getElementById('amount-display');

        if (avatar && user?.name) {
            avatar.title = user.name;
        }

        logoutButton?.addEventListener('click', async () => {
            try {
                await DairyApp.api('/api/logout', { method: 'POST', body: '{}' });
            } catch (error) {
                // Local logout should still happen if the server token is already expired.
            }

            DairyApp.clearSession();
            window.location.href = '/login';
        });

        if (qtyInput && rateDisplay && amountDisplay) {
            qtyInput.addEventListener('input', () => {
                const qty = parseFloat(qtyInput.value) || 0;
                const rate = parseFloat(rateDisplay.value.replace(/[₹,]/g, '')) || 0;
                const amt = (qty * rate).toFixed(2);
                amountDisplay.textContent = '₹' + parseFloat(amt).toLocaleString('en-IN');
            });
        }
    });
    </script>
    @stack('scripts')
</body>
</html>
