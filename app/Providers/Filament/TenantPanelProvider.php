<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Pages\EditProfile;
use App\Filament\Tenant\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('panel')
            ->login(Login::class)
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => view('filament.components.recaptcha-login-script')->render(),
            )
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            // El perfil se abre desde el menú de usuario, como en el panel de
            // administración.
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Mis datos')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn () => EditProfile::getUrl()),
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'Dispositivos',
                'Mi Cuenta',
                'Documentación',
            ])
            ->navigationItems([
                NavigationItem::make('Documentación de la API')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->group('Documentación')
                    ->sort(100)
                    ->openUrlInNewTab()
                    ->url(fn (): string => route('scribe')),
            ]);
    }
}
