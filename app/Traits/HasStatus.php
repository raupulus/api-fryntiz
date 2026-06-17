<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Trait para modelos que tienen un campo status_id o estado.
 */
trait HasStatus
{
    /**
     * Scope para filtrar registros activos/publicados.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', 2);
    }

    /**
     * Scope para filtrar borradores.
     */
    public function scopeDraft($query)
    {
        return $query->where('status_id', 1);
    }

    /**
     * Scope para filtrar contenido programado.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status_id', 3);
    }

    /**
     * Verificar si el registro está publicado.
     */
    public function isPublished(): bool
    {
        return $this->status_id === 2;
    }
}
