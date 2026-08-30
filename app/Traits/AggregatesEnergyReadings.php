<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Hardware\HardwareEnergy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lo común a las cuatro tablas de resumen —del día y acumulado— de energía.
 *
 * Aquí viven los dos arreglos de fondo (D115):
 *
 *  1. **Se agrupa por elemento y por día.** `recalculateToday()` buscaba la
 *     última fila del dispositivo *sin filtrar por fecha*, la actualizaba y le
 *     movía la fecha a hoy. Resultado: una sola fila por dispositivo desde 2022,
 *     un `count(id) as days_operating` que valía 1 siempre, y las corrientes de
 *     un panel y de un router sumadas en la misma casilla.
 *  2. **Lo que se acumula es energía, no potencia.** Sumar vatios instantáneos
 *     da un número que sube si el sensor mide más veces. Se suman `energy_wh` y
 *     `energy_ah`, que sí son magnitudes acumulables, y los mínimos y máximos
 *     siguen siendo de la magnitud instantánea, que es donde tienen sentido.
 *
 * Una lectura marcada como sospechosa no llega hasta aquí: el servicio no la
 * pasa (D72). Se conserva en su tabla, pero no ensucia los totales.
 */
trait AggregatesEnergyReadings
{
    /**
     * Columnas de mínimo y máximo de esta tabla, por dato.
     *
     * `['clave del dato' => ['min' => 'columna_min', 'max' => 'columna_max']]`.
     * Cualquiera de las dos puede faltar.
     *
     * @return array<string, array{min?: string, max?: string}>
     */
    abstract protected static function extremeColumns(): array;

    /**
     * Elemento energético al que corresponde el resumen.
     */
    public function energy(): BelongsTo
    {
        return $this->belongsTo(HardwareEnergy::class, 'hardware_energy_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfElement(Builder $query, int $energyId): Builder
    {
        return $query->where('hardware_energy_id', $energyId);
    }

    /**
     * Aplica los mínimos y máximos que traiga la lectura.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function updatedExtremes(self $summary, array $data): array
    {
        $changes = [];

        foreach (static::extremeColumns() as $key => $columns) {
            $value = $data[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $value = (float) $value;

            if (isset($columns['min'])) {
                $current = $summary->{$columns['min']};
                $changes[$columns['min']] = ($current === null || $value < (float) $current) ? $value : $current;
            }

            if (isset($columns['max'])) {
                $current = $summary->{$columns['max']};
                $changes[$columns['max']] = ($current === null || $value > (float) $current) ? $value : $current;
            }
        }

        return $changes;
    }

    /**
     * Suma que respeta el null: sin sumando nuevo, el total no cambia; sin total
     * previo, el total pasa a ser el sumando. Nunca aparece un 0 inventado.
     */
    private static function sum(float|int|string|null $total, float|int|string|null $addend): ?float
    {
        if ($addend === null || $addend === '') {
            return $total === null ? null : (float) $total;
        }

        return (float) ($total ?? 0) + (float) $addend;
    }
}
