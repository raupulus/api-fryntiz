<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\ApiCacheVersion;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Para lo que sale en la API de contenidos sin ser parte de un contenido
 * (plataformas, categorías, etiquetas, tecnologías, datos y redes del autor):
 * al guardarlo o borrarlo, las respuestas guardadas dejan de valer (P6/F9).
 */
trait BumpsApiCache
{
    protected static function bootBumpsApiCache(): void
    {
        static::saved(static fn () => ApiCacheVersion::bump());
        static::deleted(static fn () => ApiCacheVersion::bump());

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::registerModelEvent('restored', static fn () => ApiCacheVersion::bump());
        }
    }
}
