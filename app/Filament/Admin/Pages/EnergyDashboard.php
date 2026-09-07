<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Widgets\EnergyHistoricalChart;
use App\Filament\Admin\Widgets\EnergyStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Página de resumen de energía con widgets de hoy e histórico.
 */
class EnergyDashboard extends Page
{
    /**
     * Métricas eléctricas de todas las instalaciones. Es la versión a pantalla
     * completa de {@see EnergyStatsWidget}, así
     * que comparte criterio (AR-SEC-04).
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $title = 'Resumen energía';

    protected static ?string $slug = 'energy-dashboard';

    /**
     * El primero del clúster, y no por gusto.
     *
     * `/admin/energy` no es una página: su `mount()` redirige al **primer**
     * elemento de la subnavegación. Empatado a 1 con «Instalaciones», ganaba
     * ésta, así que pulsar «Energy» en las migas de pan llevaba siempre a
     * Instalaciones —y estando ya allí parecía que la página sólo se recargaba.
     *
     * El resumen es la portada del módulo, así que va delante.
     */
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.admin.pages.energy-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            EnergyStatsWidget::class,
            EnergyHistoricalChart::class,
        ];
    }
}
