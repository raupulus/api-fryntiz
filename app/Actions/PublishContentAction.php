<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatusEnum;
use App\Models\Content\Content;

/**
 * Acción responsable de publicar automáticamente los contenidos que han alcanzado su fecha de programación.
 */
class PublishContentAction
{
    /**
     * Ejecuta la consulta de actualización masiva cambiando el estado a "Publicado"
     * para aquellos registros en estado "Programado" cuya fecha sea anterior o igual a ahora.
     *
     * @return int Número total de registros afectados y publicados.
     */
    public function execute(): int
    {
        return Content::where('status_id', ContentStatusEnum::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->update([
                'status_id' => ContentStatusEnum::Published,
                'published_at' => now(),
            ]);
    }
}
