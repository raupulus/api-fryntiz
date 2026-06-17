<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoadToday;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget que muestra las estadísticas de energía del día actual.
 */
class EnergyTodayWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();
        $gen = HardwarePowerGeneratorToday::whereDate('read_at', $today)->avg('power') ?? 0;
        $load = HardwarePowerLoadToday::whereDate('read_at', $today)->avg('power') ?? 0;
        $batt = HardwarePowerGeneratorToday::whereDate('read_at', $today)
            ->selectRaw('avg((battery_percentage_min + battery_percentage_max) / 2.0) as avg_batt')
            ->value('avg_batt') ?? 0;

        return [
            Stat::make('Generación media', number_format($gen, 1).' W')
                ->descriptionIcon('heroicon-o-sun')->color('success'),
            Stat::make('Consumo medio', number_format($load, 1).' W')
                ->descriptionIcon('heroicon-o-bolt')->color('warning'),
            Stat::make('Batería', number_format($batt, 0).' %')
                ->descriptionIcon('heroicon-o-battery-100')
                ->color($batt < 30 ? 'danger' : ($batt < 60 ? 'warning' : 'success')),
        ];
    }
}
