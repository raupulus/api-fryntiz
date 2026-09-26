<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Estados de contenido con los ids que tiene producción.
 *
 * El 26-05-2026 la v2 cambió el orden en el seeder (2 = publicado, 3 =
 * programado) y todo el código se escribió encima de ese orden, mientras que
 * la base real conserva el de la v1: 2 = programado, 3 = publicado. Los tests
 * que sembraban con el seeder daban por bueno lo que en producción fallaba, y
 * la API no servía ningún contenido.
 *
 * Esto siembra el orden real de forma explícita, sin pasar por el seeder, para
 * que un test de contenidos no pueda volver a apoyarse en un orden inventado.
 */
trait SeedsProductionContentStatuses
{
    /**
     * Ids y slugs tal y como están en producción (volcado del 2026-09-24).
     *
     * @var array<int, array{0: string, 1: string}>
     */
    protected const PRODUCTION_CONTENT_STATUSES = [
        1 => ['draft', 'Borrador'],
        2 => ['programmed', 'Programado'],
        3 => ['published', 'Publicado'],
        4 => ['not-published', 'No Publicado'],
        5 => ['copyright-protected', 'Copyright Protected'],
        6 => ['to-remove', 'Para eliminar'],
    ];

    protected function seedContentStatusesAsProduction(): void
    {
        DB::table('content_available_status')->delete();

        $now = now();

        foreach (self::PRODUCTION_CONTENT_STATUSES as $id => [$slug, $name]) {
            DB::table('content_available_status')->insert([
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('content_available_status', 'id'), 6, true)");
        }
    }
}
