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
**Permissions**: Filament Shield (Spatie Laravel Permissions integration)
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
composer test                    # Run all tests
vendor/bin/pest                  # Run Pest directly
vendor/bin/pest --watch          # Watch mode
vendor/bin/pest tests/Feature/   # Run specific directory
vendor/bin/pest --filter testName # Run specific test
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
  - `Branch` - Locations with assigned employees (deletion disabled)
  - `User` - Includes 2FA, branch assignment, and activation status with HasRoles trait
- `app/Http/Controllers/` - Controllers return `Inertia::render()` responses
  - `EmployeeDashboardController` - Employee transaction views
  - `ScannerController` - QR lookup and debit processing (requires `has.branch` middleware)
- `app/Filament/Resources/` - Filament admin panel resources (warm color theme)
- `app/Filament/Pages/Auth/` - Custom authentication pages:
  - `Login.php` - Custom login with active user validation
- `app/Http/Middleware/` - Custom middleware:
  - `EnsureUserIsActive` - Blocks inactive users from accessing the system
- `app/Services/` - Business logic services:
  - `QrCodeService` - QR code generation/deletion logic
  - `TransactionService` - Transaction processing with DB transactions and validation
  - `UserImportService` - Excel-based user imports with error reporting
- `routes/web.php` - Main app routes
- `routes/auth.php` - Authentication routes (Laravel Fortify)
- `routes/settings.php` - User settings routes

**Key Integration Points:**
- **Inertia.js**: No separate API - return `Inertia::render()` from controllers
- **Laravel Wayfinder**: Type-safe routing - generates TypeScript actions in `@/actions/`
- **SSR**: Production rendering via `resources/js/ssr.tsx`
- **Middleware**:
  - `has.branch` - Checks employee branch assignment for scanner access
  - `EnsureUserIsActive` - Applied to both web and Filament stacks
- **Filament Shield**: Role-based permissions integrated with Spatie Laravel Permissions

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
- `legacy_id` field auto-generated in format "EMCAD" + 6 digits (e.g., EMCAD000001)
- Two QR codes generated per card: one with UUID, one with legacy_id
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

**Transaction Processing:**
- Use `TransactionService` for all balance operations (credit, debit, adjustment)
- Wrapped in DB transactions with balance validation
- Three transaction types:
  - `credit` - Add balance (no branch required)
  - `debit` - Subtract balance (branch required, used by scanner)
  - `adjustment` - Add or subtract balance (branch required only for negative amounts)
- All transactions record `balance_before` and `balance_after` snapshots
- Debit transactions require branch_id and validate sufficient balance

**User Activation System:**
- Users have `is_active` boolean field (default: true)
- Inactive users cannot log in to frontend or admin panel
- Custom Filament login page validates user activation status
- `EnsureUserIsActive` middleware automatically logs out inactive users
- Frontend login (Fortify) validates activation in `FortifyServiceProvider`
- Admin can activate/deactivate users from Filament panel with toggle action
- Users cannot deactivate their own account (validation in form and action)
- Filter available to view only active or inactive users

**QR Code Synchronization:**
- When user is deactivated, all their gift cards are automatically deactivated
- When user is reactivated, all their gift cards are automatically reactivated
- Synchronization handled by User model `booted()` event listening for `is_active` changes
- Feedback notification shows count of affected QR codes

**Branch Protection:**
- Branch deletion is completely disabled in Filament admin
- No delete button in edit page, table actions, or bulk actions
- Prevents accidental deletion of critical organizational data

## Claude Code Skills

This project has specialized skills installed to enhance development efficiency. Skills are invoked automatically based on context or manually via `/skill-name`.

### Available Skills

**Core Development Skills:**

1. **pest-testing** - Pest PHP 4.1 testing framework
   - **Auto-activates:** When writing tests, debugging test failures, working with assertions
   - **Manual invoke:** `/pest-testing` or when user mentions "test", "spec", "TDD"
   - **Use for:** Creating unit/feature tests, browser testing, architecture tests
   - **Key features:**
     - Prefer specific assertions (`assertSuccessful()` over `assertStatus(200)`)
     - Use datasets for repetitive validation tests
     - Browser tests with real browser integration
     - Architecture testing for code conventions
   - **Run tests:** `composer test` or `vendor/bin/pest --watch`

2. **inertia-react-development** - Inertia.js v2 + React 19
   - **Auto-activates:** When working with React pages, forms, navigation, or `<Link>`, `<Form>`, `useForm`
   - **Manual invoke:** `/inertia-react-development`
   - **Use for:** Creating/modifying React page components, client-side forms, SPA navigation
   - **Key features:**
     - Use `<Form>` component for forms (auto CSRF, validation errors)
     - Use `<Link>` for navigation (maintains SPA behavior)
     - Deferred props for progressive loading
     - Polling for real-time updates
     - WhenVisible for infinite scroll
   - **Common pitfall:** Don't use traditional `<a>` or `<form>` tags

3. **wayfinder-development** - Laravel Wayfinder type-safe routing
   - **Auto-activates:** When importing from `@/actions/` or `@/routes/`, calling Laravel routes from TypeScript
   - **Manual invoke:** `/wayfinder-development`
   - **Use for:** Type-safe route references in frontend
   - **Key patterns:**
     ```typescript
     import { store } from '@/actions/App/Http/Controllers/PostController'
     <Form {...store.form()}><input name="title" /></Form>
     store.url() // "/posts"
     show.get(1) // { url: "/posts/1", method: "get" }
     ```
   - **Regenerate routes:** `php artisan wayfinder:generate --with-form --no-interaction`

4. **tailwindcss-development** - Tailwind CSS 4.0
   - **Auto-activates:** When adding styles, working with responsive design, dark mode, or UI changes
   - **Manual invoke:** `/tailwindcss-development`
   - **Use for:** Styling components, responsive layouts, dark mode implementation
   - **Tailwind v4 specifics:**
     - Use `@import "tailwindcss"` not `@tailwind` directives
     - Use CSS `@theme` for configuration, not `tailwind.config.js`
     - Replaced utilities: `bg-black/50` not `bg-opacity-50`
     - Use `gap` utilities instead of margins for spacing
   - **Dark mode:** Always add `dark:` variants if project uses dark mode

5. **filament-docs** - FilamentPHP v4 documentation reference
   - **Auto-activates:** When working with Filament admin panel components
   - **Manual invoke:** `/filament-docs`
   - **Use for:** Looking up exact Filament implementations, method signatures, patterns
   - **Documentation structure:**
     - `forms/` - All form field types and configurations
     - `tables/` - Column types, filters, actions
     - `actions/` - Action buttons and modals
     - `general/03-resources/` - CRUD resources patterns
     - `general/10-testing/` - Filament testing guide
   - **Workflow:** Read docs → Extract patterns → Apply to code

**Advanced Skills:**

6. **devteam-laravel-skill** - AI Development Team (Opus 4.6 Agent Teams)
   - **Manual invoke:** `/devteam-laravel-skill` or describe complex feature
   - **Use for:**
     - ✅ Multi-component feature development
     - ✅ Complex system builds requiring architecture
     - ✅ Large refactoring (5+ files)
     - ✅ Complex integrations (payment gateways, external APIs)
     - ✅ Features needing comprehensive testing + documentation
   - **NOT for:**
     - ❌ Single-file changes or quick bug fixes
     - ❌ Simple CRUD operations
     - ❌ Minor documentation updates
   - **Workflow:** Planning → Development → Testing → Documentation (with approval checkpoints)
   - **Cost optimized:** Opus 4.6 with adaptive thinking, effort level tuning

### Skill Usage Guidelines

**When skills auto-activate:**
- Skills automatically engage based on context (e.g., editing a test file activates pest-testing)
- Multiple skills can be active simultaneously
- Skills provide specialized knowledge and patterns for their domain

**Manual skill invocation:**
- Use `/skill-name` when you need specialized help
- Example: `/pest-testing help me write browser tests for the scanner`
- Skills listed in system are available for use

**Best practices:**
1. Let skills auto-activate - they know when they're needed
2. Consult filament-docs before generating any Filament code
3. Use devteam-laravel for ambitious features, not simple changes
4. Always verify Wayfinder routes after backend route changes
5. Follow Tailwind v4 patterns (no v3 utilities)