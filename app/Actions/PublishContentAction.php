<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Content\Content;

/**
 * Publica los contenidos programados cuya fecha ya ha llegado.
 *
 * Lo lanza `content:publish` cada 5 minutos. Recorre los contenidos de uno en
 * uno con `Content::publish()` en vez de hacer un `update` masivo: así pasan
 * por las reglas de publicación (fecha de publicación de ese momento y
 * «Activo» marcado) y saltan los eventos del modelo, de los que depende la
 * caché de la plataforma.
 */
class PublishContentAction
{
    /**
     * @return int Contenidos publicados.
     */
    public function execute(): int
    {
        $published = 0;

        Content::query()
            ->scheduled()
            ->where('scheduled_at', '<=', now())
            ->lazyById()
            ->each(function (Content $content) use (&$published): void {
                $content->publish();
                $published++;
            });

        return $published;
    }
}
