<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Versión de lo que sirve la API de contenidos y plataformas (P6/F9 del plan
 * de contenidos del 2026-09-24).
 *
 * Forma parte de la clave de todas sus respuestas en caché: cualquier cambio en
 * un contenido, sus partes, sus taxonomías, sus ficheros o su plataforma la
 * sube, y las respuestas guardadas dejan de valer. Un contador y no sólo el
 * `updated_at`, que va al segundo: dos cambios en el mismo segundo no dejan una
 * respuesta vieja en la caché.
 */
final class ApiCacheVersion
{
    private const KEY = 'api:contents-version';

    public static function current(): int
    {
        return (int) Cache::get(self::KEY, 0);
    }

    public static function bump(): void
    {
        Cache::increment(self::KEY);
    }
}
