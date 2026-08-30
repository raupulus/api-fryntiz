<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * El **acumulado**: `hardware_power_loads_historical` y
 * `hardware_power_generators_historical`.
 *
 * Se recalcula entero a partir de los resúmenes diarios del mismo elemento, así
 * que si un día se corrige el histórico basta con volver a lanzarlo.
 */
trait AccumulatesEnergyHistory
{
    use AggregatesEnergyReadings;

    /**
     * Recalcula el acumulado del elemento a partir de sus resúmenes diarios.
     *
     * `days_operating` es el número de días **distintos** con lecturas, que es
     * lo que la palabra significa. Antes era `count(id)`, y como sólo había una
     * fila por dispositivo salía 1 desde 2022.
     */
    public static function calculateHistoricalFromTodays(
        int $hardwareDeviceId,
        ?int $hardwareEnergyId = null
    ): static {
        $modeloDelDia = static::todayModel();

        $query = $modeloDelDia::query()
            ->where('hardware_device_id', $hardwareDeviceId)
            ->when(
                $hardwareEnergyId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $hardwareEnergyId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
            );

        $selection = [
            DB::raw('count(distinct date) as days_operating'),
            DB::raw('sum(energy_wh) as energy_wh'),
            DB::raw('sum(energy_ah) as energy_ah'),
            DB::raw('sum(readings_count) as readings_count'),
        ];

        foreach (static::extremeColumns() as $columns) {
            if (isset($columns['min'])) {
                $selection[] = DB::raw("min({$columns['min']}) as {$columns['min']}");
            }

            if (isset($columns['max'])) {
                $selection[] = DB::raw("max({$columns['max']}) as {$columns['max']}");
            }
        }

        /** @var array<string, mixed> $aggregate */
        $aggregate = (clone $query)->select($selection)->first()?->toArray() ?? [];

        unset($aggregate['id']);

        $historical = static::query()
            ->where('hardware_device_id', $hardwareDeviceId)
            ->when(
                $hardwareEnergyId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $hardwareEnergyId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
            )
            ->first();

        if (! $historical) {
            $historical = static::query()->make([
                'hardware_device_id' => $hardwareDeviceId,
                'hardware_energy_id' => $hardwareEnergyId,
            ]);
        }

        $historical->forceFill($aggregate);
        $historical->read_at = Carbon::now();
        $historical->save();

        return $historical;
    }

    /**
     * Modelo de resumen diario del que se alimenta el acumulado.
     *
     * @return class-string
     */
    abstract protected static function todayModel(): string;
}
