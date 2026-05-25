# EcoCycle Smart India

EcoCycle Smart is a Laravel 12 + Vite product for responsible electronics recycling in India. It includes device impact analysis, e-waste facility routing, pickup planning, rewards, QR certificates, account workspaces, admin operations, OTP verification, JWT API access, notifications, activity logs, and dark/light UI theming.

## Stack

- Laravel 12, PHP 8.2
- Blade + Tailwind CSS 4 + Vite
- SQLite by default, ready for MySQL/PostgreSQL
- DomPDF + QR code certificate generation
- Session auth for the web workspace
- HMAC JWT access tokens and refresh-token rotation for APIs
- Optional Cloudinary avatar upload fallback to local public storage

## Upgraded Structure

```text
app/
  Http/
    Controllers/
      Api/                  JWT auth, search, notification APIs
      Sustainability/       Device, facility, pickup, rewards, certificate flows
      AccountController.php Protected workspace/profile/settings
      AdminController.php   Company operations dashboard
      AuthController.php    Login, signup, reset password, OTP verification
    Middleware/             Role and JWT middleware
  Models/                   Users, roles, permissions, notifications, logs, pickups
  Services/                 Device intelligence, dashboard, media, JWT, OTP, logging
database/
  migrations/               Core Laravel tables plus company platform tables
  seeders/                  Roles, permissions, admin test user
resources/
  css/app.css               Design system, dark mode, responsive UI
  js/app.js                 Search, theme, forms, dashboards, facility UI
  views/
    account/                Dashboard, profile, settings
    admin/                  Operations dashboard
    auth/                   Login, signup, reset, OTP
    sustainability/         Product pages and device report modal
routes/
  web.php                   Web product and workspace routes
  api.php                   JWT/search/notification API routes
```

## Run Locally

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Use `test@example.com` with the seeded password from the factory default (`password`) after `php artisan migrate --seed`, or create a new account from `/signup`.

## Quality Checks

```bash
php artisan test
npm run build
```

Both checks pass after the upgrade.
