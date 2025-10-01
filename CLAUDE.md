# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Domain

This is a **gift card management system** with QR code scanning capabilities. The system manages:
- **Gift Cards** with QR codes (UUID-based, generated via `endroid/qr-code`)
- **Transactions** (debits/credits) tracked with balance history
- **Branches** with assigned employees
- **Employee Dashboard** for viewing transactions
- **Scanner Interface** for QR code-based gift card lookups and debits
- **Admin Panel** (Filament 3) at `/admin` for managing users, branches, gift cards, and transactions

## Technology Stack

**Backend**: Laravel 12.0 with PHP 8.2+
**Frontend**: React 19 + TypeScript + Inertia.js (SPA with SSR)
**Styling**: Tailwind CSS 4.0 + shadcn/ui components
**Testing**: Pest PHP 4.1
**Database**: SQLite (development)
**Admin Panel**: Filament 3 (amber theme, activity logging via filament-logger)
**Excel Imports/Exports**: maatwebsite/excel (user imports, balance loading)
**QR Code Generation**: endroid/qr-code

## Development Commands

**Start development environment:**
```bash
composer dev
# Runs: Laravel server, queue worker, log monitoring, Vite dev server
```

**For SSR development:**
```bash
composer dev:ssr
# Builds SSR bundle then runs all services including SSR server
```

**Testing:**
```bash
composer test           # Run all tests
vendor/bin/pest         # Run Pest directly
vendor/bin/pest --watch # Watch mode
```

**Frontend commands:**
```bash
npm run dev          # Vite dev server (if not using composer dev)
npm run build        # Production build
npm run build:ssr    # Build with SSR
npm run types        # TypeScript type checking
npm run lint         # ESLint with auto-fix
npm run format       # Prettier formatting
```

**Code quality:**
```bash
./vendor/bin/pint    # PHP code formatting (Laravel Pint)
```

**Filament admin:**
```bash
php artisan filament:upgrade  # Run after composer install/update
```

## Architecture Overview

**Frontend Structure:**
- `resources/js/pages/` - Inertia.js page components
  - `dashboard.tsx` - Employee dashboard showing transaction history
  - `scanner.tsx` - QR code scanner interface (uses html5-qrcode library)
  - `auth/` - Authentication pages (login, register, 2FA)
  - `settings/` - User settings pages
  - `welcome.tsx` - Public homepage
- `resources/js/components/` - Custom React components
- `resources/js/components/ui/` - shadcn/ui components (Radix UI + Tailwind)
- `resources/js/layouts/` - Layout components (AuthLayout, AppLayout)
- `resources/js/app.tsx` - Frontend entry point
- Path alias: `@/` maps to `resources/js/`

**Backend Structure:**
- `app/Models/` - Core domain models:
  - `GiftCard` - Uses UUIDs, soft deletes, auto-generates QR codes on create/update
  - `Transaction` - Tracks balance changes with before/after snapshots
  - `Branch` - Locations with assigned employees
  - `User` - Includes 2FA columns and branch assignment
- `app/Http/Controllers/` - Controllers return `Inertia::render()` responses
  - `EmployeeDashboardController` - Employee transaction views
  - `ScannerController` - QR lookup and debit processing (requires `has.branch` middleware)
- `app/Filament/Resources/` - Filament admin panel resources
- `app/Services/QrCodeService` - QR code generation/deletion logic
- `routes/web.php` - Main app routes
- `routes/auth.php` - Authentication routes (Laravel Fortify)
- `routes/settings.php` - User settings routes

**Key Integration Points:**
- **Inertia.js**: No separate API - return `Inertia::render()` from controllers
- **Laravel Wayfinder**: Type-safe routing - generates TypeScript actions in `@/actions/`
- **SSR**: Production rendering via `resources/js/ssr.tsx`
- **Middleware**: `has.branch` checks employee branch assignment for scanner access

**Data Flow:**
1. Gift cards created with UUID primary keys, legacy IDs for QR codes
2. QR codes generated automatically on gift card creation/update (stored in `storage/app/public/qr_codes/`)
3. Scanner interface looks up cards by legacy ID, processes debits
4. Transactions log balance changes with admin user and branch tracking
5. Employee dashboard shows filtered transaction history

## Important Notes

**Component Development:**
- Follow shadcn/ui patterns for new UI components
- Use Class Variance Authority (CVA) for component variants
- Leverage existing Radix UI components in `components/ui/`
- Import patterns: `import { Button } from '@/components/ui/button'`

**Form Handling:**
- Use Wayfinder-generated form actions: `<Form {...ControllerName.action.form()}>`
- Forms include automatic CSRF protection and validation error handling
- Access form state via render props: `{({ processing, errors }) => (...)}`

**Styling:**
- Tailwind CSS 4.0 with CSS custom properties for theming
- Dark/light mode support built-in
- Use `tailwind-merge` for conditional classes

**Testing:**
- Pest is the primary testing framework (not PHPUnit)
- Tests use SQLite in-memory database
- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`
- Use `it()` syntax for test descriptions

**Queue System:**
- Database-based queue configured
- Start with `php artisan queue:work` or use `composer dev`

**Development Database:**
- SQLite configured for local development
- Database file: `database/database.sqlite`
- Run migrations after fresh install: `php artisan migrate`

**Gift Card System:**
- Gift cards use UUID primary keys (`HasUuids` trait)
- `legacy_id` field used for QR code content (user-facing identifier)
- QR codes auto-generated via model events (`created`, `updating`)
- QR image files stored in `storage/app/public/qr_codes/`
- Soft deletes enabled - QR files cleaned up on force delete

**Import/Export Features:**
- User import via Excel templates (download at `/download/users-template`)
- Balance loading via Excel templates (download at `/download/balance-template`)
- Error reports generated as downloadable Excel files
- All templates handled by `maatwebsite/excel` package

**Authentication:**
- Laravel Fortify with full 2FA support (TOTP + recovery codes)
- User model includes `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`
- Branch assignment checked via `has.branch` middleware for scanner access