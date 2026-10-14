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
