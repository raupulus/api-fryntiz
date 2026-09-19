<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\Hardware\HardwareType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resumen completo de energía: estado actual, totales de hoy y acumulados
 * de los últimos 30 días, todo en una única cuadrícula de tarjetas.
 *
 * **Sólo la instalación solar**, igual que la cabecera de `/hardware/energy`:
 * los aparatos de tipo `controlador-solar`. La Raspberry Pi 5 mide su consumo
 * enchufada a la red de casa; sumarla al consumo solar falseaba el consumo, el
 * balance neto y los acumulados de 30 días.
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

    /**
     * Los aparatos de la instalación solar, calculados una vez por render.
     *
     * @var list<int>|null
     */
    private ?array $solarIds = null;

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
        // Última lectura de cada elemento con rol de carga (consumo)
        $latestLoads = HardwareEnergyReading::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD)->where('is_active', true))
            ->whereIn('id', HardwareEnergyReading::query()
                ->selectRaw('MAX(id)')
                ->whereIn('hardware_device_id', $this->solarIds())
                ->whereNotNull('hardware_energy_id')
                ->groupBy('hardware_energy_id'))
            ->get();

        // Última lectura de cada elemento con rol de generación (producción)
        $latestGenerators = HardwareEnergyReading::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR)->where('is_active', true))
            ->whereIn('id', HardwareEnergyReading::query()
                ->selectRaw('MAX(id)')
                ->whereIn('hardware_device_id', $this->solarIds())
                ->whereNotNull('hardware_energy_id')
                ->groupBy('hardware_energy_id'))
            ->get();

        $currentConsumption = (float) $latestLoads->sum('power');
        $currentGeneration = (float) $latestGenerators->sum('power');
        $balance = $currentGeneration - $currentConsumption;

        // Baterías: porcentaje medio de los elementos activos que reportan porcentaje
        $latestBatteries = HardwareEnergyReading::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->whereNotNull('battery_percentage')
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('is_active', true))
            ->whereIn('id', HardwareEnergyReading::query()
                ->selectRaw('MAX(id)')
                ->whereIn('hardware_device_id', $this->solarIds())
                ->whereNotNull('hardware_energy_id')
                ->whereNotNull('battery_percentage')
                ->groupBy('hardware_energy_id'))
            ->get();

        $batteryAvg = (float) ($latestBatteries->avg('battery_percentage') ?? 0);

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

        $loadsToday = HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->where('date', $today)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD))
            ->get();

        $generatorsToday = HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->where('date', $today)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR))
            ->get();

        // Vatios-hora, no vatios: suma acumulada del día
        $consumptionToday = (float) $loadsToday->sum('energy_wh');
        $generationToday = (float) $generatorsToday->sum('energy_wh');
        $peakConsumptionToday = (float) ($loadsToday->max('power_max') ?? 0);

        $batteryMinToday = (float) (HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->where('date', $today)
            ->whereNotNull('battery_percentage_min')
            ->min('battery_percentage_min') ?? 0);

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

        // Acumulado de los últimos 30 días sumando los agregados diarios
        $totalConsumption = (float) HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->where('date', '>=', $since)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD))
            ->sum('energy_wh');

        $totalGeneration = (float) HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $this->solarIds())
            ->where('date', '>=', $since)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR))
            ->sum('energy_wh');

        // Métricas de odómetro histórico. **Todas las sesiones**, no sólo la
        // última: el acumulado de un elemento es la suma de sus sesiones, y
        // quedarse con la más reciente descartaba todo lo anterior al último
        // reinicio del odómetro. El panel público (`EnergyController`) siempre
        // lo leyó así; esto era la otra mitad de la contradicción.
        $allHistorical = HardwareEnergyHistorical::query()
            ->with('hardwareEnergy:id,role')
            ->whereIn('hardware_device_id', $this->solarIds())
            ->whereNotNull('hardware_energy_id')
            ->get();

        // Los días sí son un máximo y no una suma: dos elementos que llevan
        // 1.700 días cada uno no suman 3.400 días de instalación.
        $daysOperating = (int) ($allHistorical->max('days_operating') ?? 0);

        // Los ciclos, uno por batería: sumar todos los elementos contaba dos
        // veces la del Renogy, que los manda en el generador y en la batería.
        $byRole = static fn (string $role) => $allHistorical->filter(
            static fn (HardwareEnergyHistorical $r): bool => $r->hardwareEnergy?->role === $role
        );
        $cycles = HardwareEnergyHistorical::batteryCycles(
            $byRole(HardwareEnergy::ROLE_BATTERY),
            $byRole(HardwareEnergy::ROLE_GENERATOR),
        );
        $fullCharges = $cycles['full'];
        $overDischarges = $cycles['over'];

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

    /**
     * @return list<int>
     */
    private function solarIds(): array
    {
        return $this->solarIds ??= HardwareDevice::query()
            ->whereHas('type', static fn (Builder $q) => $q->where('slug', HardwareType::SOLAR_CONTROLLER_SLUG))
            ->pluck('id')
            ->all();
    }
}
