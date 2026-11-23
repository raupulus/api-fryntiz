<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\Platform;
use App\Support\ApiCacheVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio encargado de gestionar las plataformas y sitios asociados al sistema,
 * con implementación de caché versionada mediante ApiCacheVersion.
 */
class PlatformService
{
    /**
     * Recupera todas las plataformas activas junto con sus categorías y etiquetas.
     * La clave incluye ApiCacheVersion para invalidarse de forma determinista al cambiar.
     *
     * @return Collection Colección con todas las plataformas.
     */
    public function getAll(): Collection
    {
        $cacheKey = 'platforms:all:v'.ApiCacheVersion::current();

        return Cache::remember($cacheKey, 3600, function () {
            return Platform::with(['tags', 'categories', 'image'])->get();
        });
    }

    /**
     * Obtiene los detalles completos de una plataforma en base a su slug único.
     *
     * @param  string  $slug  Slug o identificador URL-friendly de la plataforma.
     * @return Platform|null Modelo de la plataforma o null si no se encuentra.
     */
    public function getBySlug(string $slug): ?Platform
    {
        // Sin relaciones: cada ruta carga lo suyo, y la ficha va a la caché.
        return Platform::query()->where('slug', $slug)->first();
    }
}
