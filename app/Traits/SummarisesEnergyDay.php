<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * El resumen **del día**: `hardware_power_loads_today` y
 * `hardware_power_generators_today`.
 *
 * Una fila por elemento y por día. Lo dice el nombre de la tabla y hasta ahora
 * no era verdad.
 */
trait SummarisesEnergyDay
{
    use AggregatesEnergyReadings;

    /**
     * Suma una lectura al resumen del día del elemento.
     *
     * `$data` acepta las claves: `energy_wh`, `energy_ah`, `read_at` y las que
     * declare `extremeColumns()` —`voltage`, `power`, `amperage`,
     * `temperature`, `battery`, `battery_percentage`, `fan`—. Las ausentes no
     * tocan nada: no se escribe un 0 donde no había dato.
     *
     * **Si el aparato manda su propio acumulado del día, ese manda.** Un
     * controlador solar lleva la cuenta él mismo y la manda en cada lectura
     * (`day_power_generation_wh`, `day_charging_amp_hours`…): sumar nuestras
     * lecturas encima daría el doble. Se pasa en `device_energy_wh` y
     * `device_energy_ah`, y sustituye al acumulado propio en vez de sumarse.
     * Si no viene, se suma lectura a lectura como siempre.
     *
     * @param  array<string, mixed>  $data
     */
    public static function recalculateToday(
        int $hardwareDeviceId,
        ?int $hardwareEnergyId,
        array $data,
        ?string $date = null
    ): static {
        $now = Carbon::now();
        $date = $date ?? $now->format('Y-m-d');
        $readAt = $data['read_at'] ?? $now;

        $summary = static::query()
            ->where('hardware_device_id', $hardwareDeviceId)
            ->when(
                $hardwareEnergyId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $hardwareEnergyId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
            )
            ->where('date', $date)
            ->first();

        if (! $summary) {
            $summary = static::query()->make([
                'hardware_device_id' => $hardwareDeviceId,
                'hardware_energy_id' => $hardwareEnergyId,
                'date' => $date,
            ]);
        }

        $summary->fill(self::updatedExtremes($summary, $data));

        $summary->energy_wh = isset($data['device_energy_wh'])
            ? (float) $data['device_energy_wh']
            : self::sum($summary->energy_wh, $data['energy_wh'] ?? null);

        $summary->energy_ah = isset($data['device_energy_ah'])
            ? (float) $data['device_energy_ah']
            : self::sum($summary->energy_ah, $data['energy_ah'] ?? null);
        $summary->readings_count = (int) ($summary->readings_count ?? 0) + 1;
        $summary->read_at = $readAt;

        $summary->save();

        return $summary;
    }
}
