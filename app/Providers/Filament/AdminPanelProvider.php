<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SecurityHeaders;
use App\Models\SiteSetting;
use App\Support\Content;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            // Branding follows Website → Branding & theme (store name, logo, favicon)
            ->brandName(fn () => SiteSetting::storeName())
            ->brandLogo(fn () => Content::imageUrl('branding.logo'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => Content::imageUrl('branding.favicon'))
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            // Bell icon: import/export results and other admin notifications
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // Authenticator-app 2FA. Every admin must set it up when ADMIN_REQUIRE_2FA is true (default in production).
            ->multiFactorAuthentication(
                AppAuthentication::make()
                    ->brandName(SiteSetting::storeName() . ' Admin')
                    ->recoverable(),
                isRequired: (bool) config('admin.require_2fa'),
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('Orders'),
                NavigationGroup::make('Catalog'),
                NavigationGroup::make('Website'),
                NavigationGroup::make('Settings'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
