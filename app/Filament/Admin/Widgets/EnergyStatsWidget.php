<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Resumen completo de energía: estado actual, totales de hoy y acumulados
 * de los últimos 30 días, todo en una única cuadrícula de tarjetas.
 */
class EnergyStatsWidget extends BaseWidget
{
    /**
     * Consumos e instalaciones eléctricas de todos los usuarios (AR-SEC-04).
     */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        return [
            ...$this->getCurrentStats(),
            ...$this->getTodayStats(),
            ...$this->getHistoricalStats(),
        ];
    }

    protected function getCurrentStats(): array
    {
        $latestLoads = HardwarePowerLoad::query()
            ->whereIn('id', HardwarePowerLoad::query()
                ->selectRaw('MAX(id)')
                ->whereNotNull('hardware_device_id')
                ->groupBy('hardware_device_id'))
            ->get();

        $latestGenerators = HardwarePowerGenerator::query()
            ->whereIn('id', HardwarePowerGenerator::query()
                ->selectRaw('MAX(id)')
                ->whereNotNull('hardware_device_id')
                ->groupBy('hardware_device_id'))
            ->get();

        $currentConsumption = (float) $latestLoads->sum('power');
        $currentGeneration = (float) $latestGenerators->sum('power');
        $balance = $currentGeneration - $currentConsumption;
        $batteryAvg = (float) ($latestGenerators->avg('battery_percentage') ?? 0);

        return [
            Stat::make('Consumo (ahora)', number_format($currentConsumption, 2).' W')
                ->description($latestLoads->count().' dispositivo(s) reportando consumo')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),

            Stat::make('Generación (ahora)', number_format($currentGeneration, 2).' W')
                ->description($latestGenerators->count().' dispositivo(s) reportando generación')
                ->descriptionIcon('heroicon-m-sun')
                ->color('success'),

            Stat::make('Balance neto (ahora)', ($balance >= 0 ? '+' : '').number_format($balance, 2).' W')
                ->description($balance >= 0 ? 'Superávit energético' : 'Déficit energético')
                ->descriptionIcon($balance >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($balance >= 0 ? 'success' : 'danger'),

            Stat::make('Batería media (ahora)', number_format($batteryAvg, 0).' %')
                ->description('Carga media de baterías monitorizadas')
                ->descriptionIcon($batteryAvg < 30 ? 'heroicon-m-battery-0' : ($batteryAvg < 60 ? 'heroicon-m-battery-50' : 'heroicon-m-battery-100'))
                ->color($batteryAvg < 30 ? 'danger' : ($batteryAvg < 60 ? 'warning' : 'success')),
        ];
    }

    protected function getTodayStats(): array
    {
        $today = now()->toDateString();

        $loadsToday = HardwarePowerLoadToday::query()->where('date', $today)->get();
        $generatorsToday = HardwarePowerGeneratorToday::query()->where('date', $today)->get();

        // Vatios-hora, no vatios: `SUM(power)` sumaba potencias instantáneas y
        // daba un número que subía si el sensor medía más veces.
        $consumptionToday = (float) $loadsToday->sum('energy_wh');
        $generationToday = (float) $generatorsToday->sum('energy_wh');
        $peakConsumptionToday = (float) ($loadsToday->max('power_max') ?? 0);
        $batteryMinToday = (float) ($generatorsToday->min('battery_percentage_min') ?? 0);

        return [
            Stat::make('Consumo (hoy)', number_format($consumptionToday, 2).' Wh')
                ->description('Energía consumida hoy')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),

            Stat::make('Generación (hoy)', number_format($generationToday, 2).' Wh')
                ->description('Energía generada hoy')
                ->descriptionIcon('heroicon-m-sun')
                ->color('success'),

            Stat::make('Pico de consumo (hoy)', number_format($peakConsumptionToday, 2).' W')
                ->description('Máximo instantáneo registrado hoy')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('Batería mínima (hoy)', number_format($batteryMinToday, 0).' %')
                ->description('Nivel más bajo de batería registrado hoy')
                ->descriptionIcon('heroicon-m-battery-0')
                ->color($batteryMinToday < 30 ? 'danger' : ($batteryMinToday < 60 ? 'warning' : 'success')),
        ];
    }

    protected function getHistoricalStats(): array
    {
        $since = now()->subDays(30)->toDateString();

        $loadsHistorical = HardwarePowerLoadHistorical::query()->where('read_at', '>=', $since)->get();
        $generatorsHistorical = HardwarePowerGeneratorHistorical::query()->where('read_at', '>=', $since)->get();

        $totalConsumption = (float) $loadsHistorical->sum('energy_wh');
        $totalGeneration = (float) $generatorsHistorical->sum('energy_wh');
        $daysOperating = (int) ($generatorsHistorical->max('days_operating') ?? 0);
        $fullCharges = (int) $generatorsHistorical->sum('number_battery_full_charges');
        $overDischarges = (int) $generatorsHistorical->sum('number_battery_over_discharges');

        return [
            Stat::make('Consumo acumulado (30d)', number_format($totalConsumption / 1000, 2).' kWh')
                ->description('Energía total consumida en los últimos 30 días')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),

            Stat::make('Generación acumulada (30d)', number_format($totalGeneration / 1000, 2).' kWh')
                ->description('Energía total generada en los últimos 30 días')
                ->descriptionIcon('heroicon-m-sun')
                ->color('success'),

            Stat::make('Días en operación', (string) $daysOperating)
                ->description('Máximo de días operativos registrados')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Cargas / descargas completas', $fullCharges.' / '.$overDischarges)
                ->description('Ciclos completos de batería (carga / descarga total)')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($overDischarges > $fullCharges ? 'warning' : 'success'),
        ];
    }
}
