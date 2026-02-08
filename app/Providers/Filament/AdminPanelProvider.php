<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsActive;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandLogo(asset('logo_dark.webp'))
            ->darkModeBrandLogo(asset('logo_light.webp'))
            ->brandLogoHeight('2rem')
            ->darkMode(true)
            ->font('Open Sans')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->homeUrl(fn () => route('filament.admin.resources.gift-cards.index'))
            ->colors([
                // Primary: Cream scale for buttons and interactive elements
                'primary' => [
                    50 => '#FDFCFA',
                    100 => '#FAF7F2',
                    200 => '#F5EFE6',
                    300 => '#EBDFC7', // Official cream
                    400 => '#DCC9A3',
                    500 => '#C5A059', // Gold accent
                    600 => '#A8863D',
                    700 => '#8A6B2F',
                    800 => '#6B5224',
                    900 => '#4D3B1A',
                    950 => '#3D352A',
                ],
                // Gray: Navy tones
                'gray' => [
                    50 => '#FDFCFA',
                    100 => '#F5F4F2',
                    200 => '#E8E6E3',
                    300 => '#D4D1CC',
                    400 => '#9E9A94',
                    500 => '#6B6660',
                    600 => '#4A4540',
                    700 => '#2D2A45',
                    800 => '#191731', // Official navy
                    900 => '#121024',
                    950 => '#0D0F1A',
                ],
                // Warning: Gold scale
                'warning' => [
                    50 => '#FDF9F0',
                    100 => '#FAF0DB',
                    200 => '#F5DFB3',
                    300 => '#E8C77A',
                    400 => '#D4AC5E',
                    500 => '#C5A059', // Official gold
                    600 => '#A8863D',
                    700 => '#8A6B2F',
                    800 => '#6B5224',
                    900 => '#4D3B1A',
                    950 => '#2F2313',
                ],
                'danger' => Color::Red,
                'success' => Color::Green,
                'info' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Dashboard removed - using QR Empleados as home
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Dashboard widgets removed
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                EnsureUserIsActive::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ]);
    }
}
