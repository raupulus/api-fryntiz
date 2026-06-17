<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait con scopes reutilizables para filtrar por rangos de fecha.
 * Útil para modelos con series temporales (sensores, estadísticas).
 */
trait HasTimestampScopes
{
    /**
     * Scope: registros de hoy.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope: registros de los últimos N días.
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: registros entre dos fechas.
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: último registro.
     */
    public function scopeLatestRecord(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
