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
    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $title = 'Resumen energía';

    protected static ?string $slug = 'energy-dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.energy-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            EnergyStatsWidget::class,
            EnergyHistoricalChart::class,
        ];
    }
}
