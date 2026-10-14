# Jay Malhar Dairy — Dairy Management Application

A complete Laravel-based milk collection management system with 10 modules, matching the approved dashboard screen designs.

## Features

| # | Module | Description |
|---|--------|-------------|
| 1 | **Dashboard** | KPI cards, collection trend chart, recent activity feed |
| 2 | **Farmer Management** | Full CRUD, search/filter, status management |
| 3 | **Milk Collection + SMS** | Daily entry form, auto-rate calc, SMS on save |
| 4 | **Rate Management** | Configure cow/buffalo/A2 rates, fat/SNF ranges, change history |
| 5 | **Invoice Generation** | Bulk generate, print-ready PDF, status tracking |
| 6 | **Bill Cycle** | 10/15/custom day cycles, auto-invoice toggle |
| 7 | **Deduction Management** | Feed cost, advances, loans, penalties |
| 8 | **Farmer History** | Full audit trail: collections, payments, KPIs |
| 9 | **Reports & Analytics** | Daily/invoice/farmer-wise reports, type breakdown, top farmers |
| 10 | **Settings** | SMS/DLT config, system info, quick actions |

## Tech Stack

- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Database:** MySQL
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** maatwebsite/excel
- **SMS:** HTTP API integration with DLT support
- **UI:** Custom CSS matching approved dashboard designs (dark sidebar + white content)

## Installation

```bash
# 1. Extract / move the project
cp -r dairy-app /var/www/dairy-app
cd /var/www/dairy-app

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Edit .env with your database credentials
# DB_DATABASE=dairy_app
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. Run migrations
php artisan migrate

# 6. Seed sample data (16 farmers, 15 days of collections, invoices, deductions)
php artisan db:seed

# 7. Start the development server
php artisan serve --host=0.0.0.0 --port=8000
```

## Default Login

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@dairyapp.com | password |
| Operator | operator@dairyapp.com | password |

## SMS Configuration

Edit `.env` to configure SMS gateway:

```
SMS_GATEWAY_URL=https://api.example.com/v2/send
SMS_API_KEY=your-api-key
SMS_SENDER_ID=DAIRY
DLT_ENTITY_ID=your-entity-id
DLT_TEMPLATE_ID=your-template-id
```

## Deployment (Hostinger)

1. Upload via File Manager or SSH
2. Point public_html to the `public/` directory
3. Set PHP version to 8.2+
4. Run `composer install --optimize-autoloader --no-dev`
5. Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Project Structure

```
dairy-app/
├── app/
│   ├── Enums/           # MilkType, FarmerStatus
│   ├── Http/Controllers/ # All 10 module controllers
│   ├── Models/           # Farmer, MilkCollection, Invoice, etc.
│   └── Services/         # SmsService
├── database/
│   ├── migrations/       # 11 migration files
│   └── seeders/          # DatabaseSeeder with sample data
├── public/
│   ├── css/app.css       # Full UI design system
│   └── js/app.js         # Client-side interactions
├── resources/views/      # All Blade templates
│   ├── auth/login.blade.php
│   ├── layouts/app.blade.php
│   ├── dashboard/
│   ├── farmers/
│   ├── milk-collection/
│   ├── milk-rates/
│   ├── invoices/
│   ├── bill-cycles/
│   ├── deductions/
│   ├── farmer-history/
│   ├── reports/
│   └── settings/
└── routes/
    ├── web.php
    └── api.php
```

## Budget: ₹68,000

- Development: ₹50,000
- Hosting/Domain (Hostinger, 4yr): ₹9,000
- SMS/DLT Registration: ₹9,000
- Delivery: 15-20 working days
- Support: 60-day free bug fix

## License

Proprietary — All rights reserved.


## Billing safeguards and automation

Run `php artisan migrate` after updating. Existing milk deliveries are preserved;
multiple deliveries per farmer/date/session are allowed. Collection POST requests
must include a UUID `request_id`. Reuse it when retrying the same delivery; use a
new UUID for each new delivery. The desktop form manages these IDs automatically.

Sent and paid invoices are finalized: their totals cannot be recalculated, and
collections/deductions cannot be added to their billed period. Draft and pending
invoices update when new deliveries or deductions are recorded. Generation also
includes inactive/deleted farmers with deliveries or applied deductions.

Rate changes, farmer deletion, cycle changes and invoice payment/status changes
require an administrator. Billing cycles and FAT/SNF rate slabs cannot overlap.

For automatic generation, run Laravel's scheduler continuously using
`php artisan schedule:work`, or run `php artisan schedule:run` every minute from
your operating system scheduler. Ended cycles with auto-generation enabled are
processed at 00:10 Asia/Kolkata. No OS scheduled task is installed by this update.
Invoice SMS requires `MSG91_INVOICE_TEMPLATE_ID` and an API key. Configure an
approved MSG91 flow with var1=farmer name, var2=invoice number, var3=net amount.
Without those credentials, invoices generate without sending SMS.

Tests on the XAMPP environment: `C:/xampp/php/php.exe artisan test`.
Use a PHP installation with PDO SQLite enabled. Tests use an in-memory database
and a local APP_URL, without modifying the application's MySQL data.


## Windows NativePHP development

From the project root in PowerShell:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\start-native.ps1
```

This launcher extracts the complete bundled PHP 8.3 Windows runtime, enables
required extensions, migrates the separate NativePHP development database and
starts Electron with visible logs. Keep the terminal open while using the app.
It avoids the system PHP 8.5 mismatch and the package's incomplete unzip step.
Electron dependencies must already be installed in the NativePHP vendor package.
The existing API_BASE_URL/APP_URL still determine which backend the UI calls.


## Shared desktop and web data

Start the MySQL service in XAMPP. In a terminal, run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\start-web.ps1
```

Open `http://127.0.0.1:8000` in your browser. In another terminal, run
`powershell -NoProfile -ExecutionPolicy Bypass -File .\start-native.ps1`.
Both interfaces use the web API and its MySQL records. Keep the web backend
running while using the desktop app. The NativePHP SQLite database is used by
the desktop runtime, not as a second copy of farmer/collection/invoice data.

For hosted use, deploy this code and migrations to your web server, set its
APP_URL to your HTTPS website URL, leave API_BASE_URL unset, and set
DESKTOP_API_BASE_URL on desktop installations to that website's `/api` URL.
For a subdirectory deployment include the full subdirectory in the desktop API
URL. Desktop requires access to this backend; this setup does not synchronize
an offline copy. Run billing automation on the web backend scheduler.


## Windows installer for the live website

Build the NativePHP Windows x64 installer with:

```powershell
node .\build-desktop.cjs
```

The default hosted URL is
`https://booking.advancetaxiservice.com/test-project/public/`.
To target another HTTPS deployment, pass its URL as the first argument.
The installer is written to `dist/1.0.2/JayMalharDairy-1.0.2-Setup.exe`.
The installed desktop window opens the hosted website directly, so it uses the
live login and server data without a local web/MySQL server. Internet access is
required. Website updates appear automatically in this desktop window.

The build prepares a separate sanitized application environment with its own
random application key. It excludes the development database, logs, sessions,
local `.env`, and SMS/database credentials. Development `.env` stays unchanged.
The Windows runtime and Electron dependencies must already be installed.


Desktop 1.0.1 includes the NativePHP preload files, prevents duplicate launches,
uses a dynamically selected loopback API port, and keeps live navigation and API
requests inside the hosted project subdirectory. This also supports older live
pages that still use root-relative dashboard links.

Navigation regression checks: `node desktop/navigation.test.cjs`.
Read-only packaged smoke check (launch with `--remote-debugging-port=9224` first):
`node desktop/verify.cjs 9224`.


Desktop 1.0.2 additionally redirects a root-domain home/logout URL back into
`/test-project/public/`, avoiding the hosting root's 403 response. When updating,
close all app windows and remove older 1.0.0 installations/shortcuts before using
the new installer. A leftover Program Files installation can otherwise keep
launching the old app even after a per-user update was installed.
