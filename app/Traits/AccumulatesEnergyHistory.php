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
     * Campos del acumulado que cuentan unidades, no magnitudes.
     */
    private const CONTADORES_ENTEROS = [
        'days_operating',
        'number_battery_over_discharges',
        'number_battery_full_charges',
    ];

    /**
     * Recalcula el acumulado del elemento a partir de sus resúmenes diarios.
     *
     * `days_operating` es el número de días **distintos** con lecturas, que es
     * lo que la palabra significa. Antes era `count(id)`, y como sólo había una
     * fila por dispositivo salía 1 desde 2022.
     *
     * **Si el aparato manda su propio total, ese manda… pero el acumulado nunca
     * baja.** Un controlador solar cuenta desde que se instaló, incluidos los
     * años en que estos resúmenes no existían, así que su total vale más que
     * sumar nuestros días. Pero un controlador se resetea —y entonces vuelve a
     * contar desde cero—, y ese día su «total» es menor que lo que ya hay
     * guardado: escribirlo borraría el histórico de años.
     *
     * Es la regla que tenía la V1 (`HardwarePowerGeneratorHistorical::updateModel`
     * de la rama `main`: `($power > $this->power) ? $power : $this->power`), y
     * por eso el Rover de producción tiene 66.388 Wh acumulados mientras el
     * aparato dice 36.087: se reinició en algún momento y el histórico bueno se
     * conservó.
     *
     * Se pasa en `$deviceTotals` con las claves `energy_wh`, `energy_ah` y
     * `days_operating`. Lo que no venga se calcula sumando los resúmenes
     * diarios.
     *
     * @param  array<string, mixed>  $deviceTotals
     */
    public static function calculateHistoricalFromTodays(
        int $hardwareDeviceId,
        ?int $hardwareEnergyId = null,
        array $deviceTotals = []
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

        // Lo que ya había guardado, antes de que el agregado lo pise: es la
        // memoria de todo lo anterior a estos resúmenes, y de lo que el aparato
        // contó antes de reiniciarse.
        $previos = [];

        foreach (array_keys($deviceTotals) as $campo) {
            $previos[$campo] = $historical->exists ? $historical->{$campo} : null;
        }

        $historical->forceFill($aggregate);

        foreach ($deviceTotals as $campo => $valor) {
            if ($valor === null) {
                continue;
            }

            // Se queda el mayor de los tres: lo que ya había, lo que suman los
            // resúmenes diarios y lo que dice el aparato. Nunca a la baja.
            $candidatos = array_filter(
                [$previos[$campo], $historical->{$campo}, $valor],
                static fn ($v) => $v !== null
            );

            $mayor = max(array_map(static fn ($v) => (float) $v, $candidatos));

            // Los contadores son enteros: días, ciclos de carga y de descarga.
            $historical->{$campo} = in_array($campo, self::CONTADORES_ENTEROS, true)
                ? (int) $mayor
                : $mayor;
        }

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
