<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\Login;
use App\Filament\Admin\Pages\Profile;
use App\Models\Platform;
use Filament\Enums\ThemeMode;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Sky,
                'gray' => Color::Zinc,
                'danger' => Color::Rose,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->discoverClusters(in: app_path('Filament/Admin/Clusters'), for: 'App\\Filament\\Admin\\Clusters')
            ->pages([
                Dashboard::class,
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
                'Sistema',
                'Contenido',
                'Hardware',
                'Gestión',
                'Módulos',
                'Configuración',
            ])
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view()->yieldPushContent('scripts'),
            )
            ->defaultThemeMode(ThemeMode::Dark)
            ->font('Figtree')
            ->navigationItems(
                (function () {
                    try {
                        return Platform::query()->orderBy('title')->get()->map(fn ($p) => NavigationItem::make($p->title)
                            ->icon('heroicon-o-rectangle-stack')
                            ->group('Contenido')
                            ->sort(10 + $p->id)
                            ->url('/admin/content/contents?filters[platform_id][value]='.$p->id)
                        )->all();
                    } catch (\Throwable) {
                        return [];
                    }
                })()
            )
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Editar perfil')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn () => Profile::getUrl()),
            ]);
    }
}
