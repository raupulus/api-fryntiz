<?php

declare(strict_types=1);

namespace App\Jobs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Suma una visita al contador diario de un contenido.
 *
 * Antes hacía UPDATE, y si no había tocado ninguna fila un INSERT, y si el
 * INSERT chocaba por clave duplicada capturaba la excepción **comparando el
 * texto del mensaje** («duplicate key») para volver a intentar el UPDATE. Tres
 * viajes a la base de datos en el peor caso y una condición de carrera resuelta
 * a base de leer mensajes de error.
 *
 * `content_daily_views` ya tiene un índice único sobre `(content_id, date)`, así
 * que basta un `INSERT ... ON CONFLICT DO UPDATE`: una sola sentencia, atómica,
 * sin carrera que resolver.
 */
class ProcessContentViewJob implements ShouldQueue
{
    use Queueable;

    /**
     * Tres intentos, separándolos: si la base de datos está saturada, insistir
     * de inmediato no ayuda.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    public int $tries = 3;

    public function __construct(
        public readonly int $contentId,
        public readonly CarbonInterface $viewedAt,
    ) {}

    public function handle(): void
    {
        $date = $this->viewedAt->toDateString();
        $now = now();

        DB::table('content_daily_views')->upsert(
            [[
                'content_id' => $this->contentId,
                'date' => $date,
                'views' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['content_id', 'date'],
            [
                'views' => DB::raw('content_daily_views.views + 1'),
                'updated_at' => $now,
            ],
        );
    }
}
