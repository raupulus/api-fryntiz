<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generación contra consumo de los últimos 30 días, en vatios-hora.
 *
 * Agrupa las sumas diarias de `HardwareEnergyToday` según el rol
 * (`generator` vs `load`), garantizando series temporales completas.
 */
class EnergyHistoricalChart extends ChartWidget
{
    /**
     * Histórico de producción y consumo de todas las instalaciones (AR-SEC-04).
     */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Generación vs consumo — 30 días (Wh)';

    private const DAYS = 30;

    protected function getData(): array
    {
        $from = now()->subDays(self::DAYS - 1)->startOfDay();
        $days = collect(CarbonPeriod::create($from, now()->startOfDay()))
            ->map(static fn ($day) => $day->format('Y-m-d'));

        $generation = $this->byDay(HardwareEnergy::ROLE_GENERATOR, $from->toDateString());
        $consumption = $this->byDay(HardwareEnergy::ROLE_LOAD, $from->toDateString());

        return [
            'datasets' => [
                [
                    'label' => 'Generación (Wh)',
                    'data' => $days->map(static fn (string $day) => round((float) ($generation[$day] ?? 0), 2)),
                    'borderColor' => 'rgb(34,197,94)',
                    'fill' => false,
                ],
                [
                    'label' => 'Consumo (Wh)',
                    'data' => $days->map(static fn (string $day) => round((float) ($consumption[$day] ?? 0), 2)),
                    'borderColor' => 'rgb(239,68,68)',
                    'fill' => false,
                ],
            ],
            'labels' => $days->map(static fn (string $day) => substr($day, 8, 2).'/'.substr($day, 5, 2)),
        ];
    }

    /**
     * Vatios-hora por día para un rol energético, sumando todos los elementos.
     *
     * @return Collection<string, float>
     */
    private function byDay(string $role, string $from): Collection
    {
        return HardwareEnergyToday::query()
            ->where('date', '>=', $from)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', $role))
            ->groupBy('date')
            ->select(['date', DB::raw('sum(energy_wh) as total')])
            ->pluck('total', 'date')
            ->mapWithKeys(static fn ($total, $day) => [substr((string) $day, 0, 10) => (float) $total]);
    }

    protected function getType(): string
    {
        return 'line';
    }
}
