<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class AirFlightRemoveDuplicateRoutesCommand
 *
 * Antes de que la fusión por `messages` de `AirFlightService::addAircraft()`
 * (2026-09-08) evitara que se sigan generando, subidas repetidas del mismo
 * sondeo dejaban en `airflight_routes` varias filas idénticas: mismo avión
 * (`airplane_id`), mismo instante detectado (`seen_at`) y mismo contador de
 * mensajes decodificados (`messages`). No son puntos de ruta distintos —es
 * la misma detección subida dos, tres o más veces—, así que sobran todas
 * menos una.
 *
 * Este comando es la limpieza de lo que ya quedó guardado con ese ruido,
 * no la prevención (eso ya está resuelto en la ingesta). Solo actúa sobre
 * ese trío exacto (`airplane_id` + `seen_at` + `messages`); no toca las
 * filas sin posición que se guardan a propósito (ver
 * docs/info/airflight.md, "airflight_routes guarda TODO").
 *
 * Seguridad, mismo patrón que `keycounter:remove_duplicate`:
 *
 * - Sin `--force`: solo cuenta y registra cuántas filas se borrarían. No
 *   toca la base de datos. Es el modo por defecto a propósito.
 * - Con `--force`: borra de verdad.
 * - `--date=YYYY-MM-DD`: acota la revisión a ese día (por `seen_at`). Sin
 *   este flag se revisa la tabla completa. Sirve tanto para repasar un día
 *   concreto antes de fiarte del resultado como para trocear un borrado
 *   grande en varias ejecuciones (una por día) si la tabla es enorme.
 *
 * De cada grupo de duplicados se conserva la fila con el `id` más bajo —la
 * primera que se guardó—, igual que hace `keycounter:remove_duplicate`.
 */
class AirFlightRemoveDuplicateRoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'airflight:remove_duplicate_routes
        {--date= : Fecha (YYYY-MM-DD) a la que acotar la revisión, por `seen_at`. Sin este flag se revisa la tabla completa.}
        {--force : Ejecuta el borrado real. Sin este flag no se borra nada, solo se cuenta y registra.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detecta (y, con --force, borra) subidas duplicadas en airflight_routes: mismo avión, mismo instante y mismo contador de mensajes';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $date = $this->option('date');

        if ($date !== null && ! $this->isValidDate($date)) {
            $this->error("Fecha inválida: «{$date}». Formato esperado: YYYY-MM-DD (ej. 2026-09-08).");

            return self::FAILURE;
        }

        $start = microtime(true);
        $dateClause = $date !== null ? "WHERE seen_at::date = '{$date}'" : '';

        $found = (int) DB::selectOne("
            SELECT COUNT(*) AS total FROM (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY airplane_id, seen_at, messages ORDER BY id
                ) AS rn
                FROM airflight_routes
                {$dateClause}
            ) t
            WHERE rn > 1
        ")->total;

        $deleted = 0;

        if ($force && $found > 0) {
            $deleted = DB::delete("
                DELETE FROM airflight_routes t
                USING (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY airplane_id, seen_at, messages ORDER BY id
                    ) AS rn
                    FROM airflight_routes
                    {$dateClause}
                ) d
                WHERE t.id = d.id AND d.rn > 1
            ");
        }

        $elapsed = round(microtime(true) - $start, 3);
        $alcance = $date !== null ? "fecha={$date}" : 'tabla completa';
        $modo = $force ? 'BORRADO' : 'DRY-RUN (no se ha borrado nada)';

        $message = "[airflight:remove_duplicate_routes] alcance={$alcance} modo={$modo} "
            ."duplicados_detectados={$found} filas_borradas={$deleted} tiempo={$elapsed}s";

        $this->info($message);
        Log::info($message);

        if (! $force && $found > 0) {
            $this->line('Vuelve a ejecutar con --force para borrar de verdad.');
        }

        return self::SUCCESS;
    }

    /**
     * `strtotime`/`Carbon::parse` aceptan formatos ambiguos («09/08/2026») que
     * aquí no interesan: mejor un formato único y explícito que un borrado
     * con la fecha equivocada por una interpretación distinta a la
     * esperada.
     */
    private function isValidDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
    }
}
