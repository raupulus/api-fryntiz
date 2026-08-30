<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Hardware\HardwareEnergy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lo común a las tablas de lecturas de energía (D115).
 *
 * Una lectura es siempre de un **elemento** —un panel, un router—, no sólo del
 * dispositivo que lo mide, y guarda los tres crudos (`amperage`, `voltage`,
 * `delta_seconds`) además de los derivados. Los crudos no se tiran nunca: si un
 * día se descubre que un elemento tenía la tensión mal puesta, con ellos se
 * recalcula el histórico entero.
 *
 * Y una lectura rara **se marca, no se descarta** (D72). Queda fuera de los
 * agregados del día, pero sigue ahí para poder mirarla.
 */
trait IsEnergyReading
{
    /**
     * Elemento energético al que corresponde la lectura.
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
     * Las que entran en los agregados: todo menos las marcadas.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeReliable(Builder $query): Builder
    {
        return $query->where('is_suspicious', false);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Rango temporal por `read_at`, que es cuándo se midió, no cuándo llegó.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $hasta): Builder
    {
        return $query
            ->when($from, static fn (Builder $q, string $d) => $q->where('read_at', '>=', $d))
            ->when($hasta, static fn (Builder $q, string $h) => $q->where('read_at', '<=', $h));
    }

    /**
     * Marca la lectura como sospechosa acumulando motivos: una misma lectura
     * puede tener la corriente negativa **y** no tener tensión.
     */
    public function markSuspicious(string $reason): static
    {
        $previos = $this->suspicious_reason !== null && $this->suspicious_reason !== ''
            ? $this->suspicious_reason.'; '
            : '';

        $this->is_suspicious = true;
        $this->suspicious_reason = mb_substr($previos.$reason, 0, 255);

        return $this;
    }
}
