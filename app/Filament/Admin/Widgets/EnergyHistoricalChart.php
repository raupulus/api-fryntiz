<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoadToday;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generación contra consumo de los últimos 30 días, en vatios-hora.
 *
 * Dos cosas que estaban mal y no se veían porque la gráfica pintaba algo:
 *
 *  - **Leía las tablas de acumulado**, que tienen una fila por elemento
 *    recalculada entera, no una por día. Pintaba el acumulado como si fuera una
 *    serie temporal.
 *  - **Pintaba `power`**, potencia instantánea, con etiqueta de vatios. Lo que
 *    tiene sentido acumular por día son vatios-hora.
 *
 * Y el eje de fechas se construye a partir del periodo, no de las filas: si un
 * día no hay generación pero sí consumo, las dos series tienen que seguir
 * cuadrando con la misma etiqueta.
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

        $generation = $this->byDay(HardwarePowerGeneratorToday::class, $from->toDateString());
        $consumption = $this->byDay(HardwarePowerLoadToday::class, $from->toDateString());

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
     * Vatios-hora por día, sumando todos los elementos.
     *
     * @param  class-string  $model
     * @return Collection<string, float>
     */
    private function byDay(string $model, string $from): Collection
    {
        return $model::query()
            ->where('date', '>=', $from)
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
