<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-base-url" content="{{ config('app.api_base_url') }}">
    <title>Login — Jay Malhar Dairy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --sidebar: #0F172A; --accent: #10B981; --accent-hover: #059669;
            --bg: #F1F5F9; --card: #FFFFFF; --text: #1E293B; --text-muted: #64748B;
            --border: #E2E8F0; --danger: #EF4444;
            --font-heading: 'Inter', sans-serif; --font-body: 'DM Sans', sans-serif;
            --radius: 10px; --radius-sm: 6px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: var(--bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { display: flex; width: 800px; min-height: 480px; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .login-left { background: var(--sidebar); width: 45%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; color: #fff; }
        .login-left .logo { width: 56px; height: 56px; background: var(--accent); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 20px; }
        .login-left h2 { font-family: var(--font-heading); font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .login-left p { color: #94A3B8; font-size: 14px; text-align: center; line-height: 1.6; }
        .login-right { flex: 1; background: var(--card); padding: 48px 40px; display: flex; flex-direction: column; justify-content: center; }
        .login-right h1 { font-family: var(--font-heading); font-size: 28px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .login-right .subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 32px; }
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .form-input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: var(--font-body); color: var(--text); background: #fff; transition: border 0.2s; }
        .form-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; background: var(--accent); color: #fff; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; font-family: var(--font-body); cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
        .alert-danger { background: #FEF2F2; color: #991B1B; padding: 10px 14px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 16px; border: 1px solid #FECACA; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo"><i class="fas fa-cow"></i></div>
            <h2>Jay Malhar Dairy</h2>
            <p>Dairy application for Milk Collection Management, Billing &amp; Reporting</p>
        </div>
        <div class="login-right">
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to your account to continue</p>

            <div class="alert-danger" id="login-error" style="display:none;"></div>

            <form id="login-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#64748B;cursor:pointer;">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            <p style="text-align:center;margin-top:20px;font-size:12px;color:#94A3B8;">
                Default: admin@dairyapp.com / password
            </p>
        </div>
    </div>
    <script>
    const DairyApp = {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        apiBase: document.querySelector('meta[name="api-base-url"]')?.content || '',
        tokenKey: 'dairy_api_token',
        userKey: 'dairy_api_user',

        token() {
            return localStorage.getItem(this.tokenKey);
        },

        setSession(token, user = null) {
            localStorage.setItem(this.tokenKey, token);
            if (user) localStorage.setItem(this.userKey, JSON.stringify(user));
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
            const headers = {
                'Accept': 'application/json',
                ...(!isFormData ? { 'Content-Type': 'application/json' } : {}),
                ...(isSameOriginApi ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...(options.headers || {}),
            };

            const response = await fetch(this.apiUrl(path), {
                credentials: 'omit',
                headers,
                ...options,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message || Object.values(data.errors || {}).flat()[0] || 'Request failed.';
                throw new Error(message);
            }

            return data;
        },

        formPayload(form) {
            const payload = {};
            const formData = new FormData(form);

            for (const [key, value] of formData.entries()) {
                if (key === '_token' || key === '_method') continue;
                payload[key] = value;
            }

            form.querySelectorAll('input[type="checkbox"][name]').forEach((checkbox) => {
                payload[checkbox.name] = checkbox.checked;
            });

            return payload;
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (DairyApp.token()) {
            window.location.href = '/dashboard';
            return;
        }

        const form = document.getElementById('login-form');
        const errorBox = document.getElementById('login-error');
        const button = form.querySelector('button[type="submit"]');

        const showError = (message) => {
            errorBox.textContent = message;
            errorBox.style.display = '';
        };

        const readToken = (response) => response.token
            || response.access_token
            || response.data?.token
            || response.data?.access_token
            || response.data?.plainTextToken;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            errorBox.style.display = 'none';
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In';

            try {
                const result = await DairyApp.api('/api/login', {
                    method: 'POST',
                     headers: {
                            "Content-Type": "application/json"
                        },
                    body: JSON.stringify(DairyApp.formPayload(form)),
                });
                const token = readToken(result);

                if (!token) {
                    throw new Error('Login API did not return a token.');
                }

                DairyApp.setSession(token, result.user || result.data?.user || null);
                window.location.href = '/dashboard';
            } catch (error) {
                showError(error.message);
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            }
        });
    });
    </script>
</body>
</html>
