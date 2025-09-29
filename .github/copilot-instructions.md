# GitHub Copilot Instructions

## Architecture Overview

This is a **Laravel 12 + React 19 + Inertia.js SPA** with server-side rendering support. The frontend is built with TypeScript, Tailwind CSS 4.0, and shadcn/ui components based on Radix UI.

### Key Integration Patterns

**Inertia.js SSR Stack**: No separate API layer - controllers return `Inertia::render()` responses directly to React pages in `resources/js/pages/`. SSR is configured via `resources/js/ssr.tsx` for production SEO.

**Laravel Wayfinder**: Provides type-safe routing between Laravel and React. Controllers generate TypeScript actions in `@/actions/App/Http/Controllers/` that can be imported in React components with full type safety.

**Authentication Flow**: Uses Laravel Fortify with full 2FA support including TOTP and recovery codes. The User model includes `two_factor_*` columns for managing 2FA state.

## Development Workflow

**Primary Commands**:

```bash
composer dev        # Starts all services: Laravel server, queue worker, logs, Vite
composer dev:ssr    # Same as above but with SSR server instead of Vite dev
composer test       # Runs Pest test suite with config clearing
```

**Frontend Development**:

- React pages go in `resources/js/pages/` and map 1:1 to Laravel routes
- Use `Form` component from `@inertiajs/react` for form handling with validation
- shadcn/ui components are in `resources/js/components/ui/` - follow existing CVA patterns
- Custom components use TypeScript interfaces for props and import from `@/` aliases

**Backend Patterns**:

- Controllers return `Inertia::render('page-name', $data)` responses
- Use `Resources/js/pages/{name}.tsx` for the page component structure
- Database uses SQLite for development (`database/database.sqlite`)
- Queue system is database-backed - ensure `queue:work` is running

## Testing Conventions

**Pest Framework**: Uses Pest (not PHPUnit) with `it()` syntax. Feature tests automatically use `RefreshDatabase` trait.

**Inertia Testing**: Test routes return status 200 and authentication redirects work with `$this->assertAuthenticated()` and `route()` helpers.

**Two-Factor Tests**: 2FA tests check for redirect to `two-factor.login` route when enabled via `Features::twoFactorAuthentication()`.

## Component Architecture

**shadcn/ui Integration**:

- Components are in `resources/js/components/ui/`
- Use Class Variance Authority (CVA) for variants
- Import patterns: `import { Button } from '@/components/ui/button'`
- Theme system uses CSS custom properties for dark/light mode

**Layout System**:

- `AuthLayout` for auth pages with title/description props
- Main layouts in `resources/js/layouts/`
- App shell components like `AppHeader`, `AppSidebar` for authenticated areas

**Form Handling**:

```tsx
<Form {...ControllerName.action.form()} resetOnSuccess={['password']}>
  {({ processing, errors }) => (
    // Form fields with error handling
  )}
</Form>
```

## File Organization Rules

**Routes**:

- `routes/web.php` - main app routes
- `routes/auth.php` - authentication routes (included in web.php)
- `routes/settings.php` - user settings routes

**Controllers**: Follow standard Laravel structure with Inertia responses. Auth controllers extend `Controller` and use Fortify features.

**Migrations**: Uses timestamped migrations. The 2FA migration adds columns to existing users table.

**Admin Panel**: Filament 3 admin panel configured at `/admin` path with amber primary color theme.

## Configuration Notes

**Vite**: Configured with React, Tailwind CSS 4.0 via plugin, and Laravel Wayfinder for type-safe routing generation.

**TypeScript**: Strict type checking with path aliases (`@/` maps to `resources/js/`). Components use explicit interfaces for props.

**Queue Processing**: Database driver configured - use `composer dev` to auto-start queue worker, or `php artisan queue:work` manually.

**Database**: SQLite for development. Touch `database/database.sqlite` file on fresh installs before migrations.
