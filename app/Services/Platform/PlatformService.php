<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio encargado de gestionar las plataformas y sitios asociados al sistema, 
 * con implementación de caché para mejorar el rendimiento de consultas recurrentes.
 */
class PlatformService
{
    /**
     * Recupera todas las plataformas activas junto con sus categorías y etiquetas.
     * Los resultados son almacenados en caché por 3600 segundos (1 hora).
     *
     * @return \Illuminate\Database\Eloquent\Collection Colección con todas las plataformas.
     */
    public function getAll(): Collection
    {
        return Cache::remember('platforms.all', 3600, function () {
            return Platform::with(['tags', 'categories'])->get();
        });
    }

    /**
     * Obtiene los detalles completos de una plataforma en base a su slug único.
     *
     * @param string $slug Slug o identificador URL-friendly de la plataforma.
     * @return \App\Models\Platform|null Modelo de la plataforma o null si no se encuentra.
     */
    public function getBySlug(string $slug): ?Platform
    {
        return Platform::with(['tags', 'categories'])
            ->where('slug', $slug)
            ->first();
    }
}
