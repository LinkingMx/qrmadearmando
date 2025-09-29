# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Technology Stack

**Backend**: Laravel 12.0 with PHP 8.2+
**Frontend**: React 19 + TypeScript + Inertia.js (SPA with SSR)
**Styling**: Tailwind CSS 4.0 + shadcn/ui components
**Testing**: Pest PHP 4.1
**Database**: SQLite (development)

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

## Architecture Overview

**Frontend Structure:**
- `resources/js/pages/` - Inertia.js page components (route to these from Laravel)
- `resources/js/components/` - Custom React components
- `resources/js/components/ui/` - shadcn/ui components (Radix UI + Tailwind)
- `resources/js/layouts/` - Layout components
- `resources/js/app.tsx` - Frontend entry point

**Backend Structure:**
- Standard Laravel MVC with Inertia.js integration
- `routes/web.php` - Main application routes
- `routes/auth.php` - Authentication routes (Laravel Fortify)
- Authentication includes full 2FA with recovery codes

**Key Integration Points:**
- **Inertia.js**: No separate API needed - return Inertia responses from controllers
- **Laravel Wayfinder**: Type-safe routing between Laravel and React
- **SSR**: Configured for production SEO/performance benefits

## Important Notes

**Component Development:**
- Follow shadcn/ui patterns for new UI components
- Use Class Variance Authority (CVA) for component variants
- Leverage existing Radix UI components in `components/ui/`

**Styling:**
- Tailwind CSS 4.0 with CSS custom properties for theming
- Dark/light mode support built-in
- Use `tailwind-merge` for conditional classes

**Testing:**
- Pest is the primary testing framework (not PHPUnit)
- Tests use SQLite in-memory database
- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`

**Queue System:**
- Database-based queue configured
- Start with `php artisan queue:work` or use `composer dev`

**Development Database:**
- SQLite configured for local development
- Database file: `database/database.sqlite`