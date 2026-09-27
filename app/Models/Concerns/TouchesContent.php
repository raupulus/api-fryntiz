<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Content\Content;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Para las partes de un contenido (páginas, SEO, enlaces, ficheros, pivotes):
 * al guardarlas o borrarlas se «toca» el contenido, que cambia de versión, y
 * la API deja de servir la respuesta guardada (P6/F9).
 *
 * Lo hace con una consulta, sin cargar el contenido: nada de carga perezosa ni
 * de eventos del contenido.
 */
trait TouchesContent
{
    protected static function bootTouchesContent(): void
    {
        $touch = static fn ($model) => Content::markChanged($model->getAttribute('content_id'));

        static::saved($touch);
        static::deleted($touch);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::registerModelEvent('restored', $touch);
        }
    }
}
