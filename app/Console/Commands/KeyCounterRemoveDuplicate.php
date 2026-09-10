<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class KeyCounterRemoveDuplicate
 *
 * La versión anterior reprocesaba las tablas enteras en cada ejecución
 * semanal (sin índice de apoyo, sin ventana temporal), lo que se traducía en
 * horas de trabajo cada vez más largas conforme crecía el histórico.
 *
 * Los duplicados solo se generan por reintentos de los dispositivos IoT al
 * insertar, es decir, siempre entre filas recientes entre sí — nunca contra
 * una fila de hace meses. Por eso el modo normal solo revisa una ventana
 * (`--window-days`, 15 por defecto: el doble de la cadencia semanal, de
 * margen) en vez del histórico completo.
 *
 * `--force` es el interruptor de seguridad: sin él, el comando solo cuenta y
 * registra lo que borraría, sin tocar datos. Cuando se confirme que la
 * detección funciona como se espera, basta con añadir `--force` a la llamada
 * en `routes/console.php` para que el borrado sea real.
 *
 * `--full` ignora la ventana y revisa la tabla completa. Sirve para la
 * limpieza puntual de duplicados históricos (anteriores a esta versión del
 * comando), que la ventana nunca llegaría a tocar. Uso manual, no programado.
 */
class KeyCounterRemoveDuplicate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keycounter:remove_duplicate
        {--force : Ejecuta el borrado real. Sin este flag no se borra nada, solo se cuenta y registra.}
        {--window-days=15 : Días hacia atrás a revisar en modo normal.}
        {--full : Ignora la ventana temporal y revisa la tabla completa (uso puntual, no programado).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina de la base de datos todos los registros duplicados que pudieran haber';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $force = (bool) $this->option('force');
        $full = (bool) $this->option('full');
        $windowDays = max(1, (int) $this->option('window-days'));

        $this->processTable(
            'keycounter_keyboard',
            ['hardware_device_id', 'start_at', 'end_at', 'pulsations'],
            $windowDays,
            $full,
            $force,
        );

        $this->processTable(
            'keycounter_mouse',
            ['hardware_device_id', 'start_at', 'end_at', 'total_clicks'],
            $windowDays,
            $full,
            $force,
        );
    }

    /**
     * Detecta (y, en modo --force, borra) los duplicados de una tabla.
     */
    private function processTable(
        string $table,
        array $groupByColumns,
        int $windowDays,
        bool $full,
        bool $force,
    ): void {
        $start = microtime(true);
        $groupByCols = implode(', ', $groupByColumns);
        $windowClause = $full ? '' : "WHERE created_at >= now() - interval '{$windowDays} days'";

        $found = (int) DB::selectOne("
            SELECT COUNT(*) AS total FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY {$groupByCols} ORDER BY id) AS rn
                FROM {$table}
                {$windowClause}
            ) t
            WHERE rn > 1
        ")->total;

        $deleted = 0;

        if ($force && $found > 0) {
            // Qué meses toca el borrado, **antes** de borrarlos: después ya no
            // hay forma de saberlo. Sin esto, un duplicado de un mes cerrado se
            // iba de la base de datos y su gráfica se quedaba puesta con la
            // cifra vieja, guardada «para siempre» y sin nada que la vuelva a
            // calcular (auditoría de la caché, 2026-09-10).
            $periods = $this->affectedPeriods($table, $groupByCols, $windowClause);

            $deleted = DB::delete("
                DELETE FROM {$table} t
                USING (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY {$groupByCols} ORDER BY id) AS rn
                    FROM {$table}
                    {$windowClause}
                ) d
                WHERE t.id = d.id AND d.rn > 1
            ");

            // Sólo el teclado: la gráfica, los widgets y los totales anuales
            // salen de `keycounter_keyboard`. Lo del ratón que se cachea es su
            // tarjeta de resumen, que es un dato vivo y se reescribe sola en el
            // siguiente refresco horario.
            if ($table === 'keycounter_keyboard' && $periods !== []) {
                KeyCounterCache::forgetPeriods($periods);

                $this->line('  · Caché olvidada de '.count($periods).' meses. '
                    .'Se rehará sola en el próximo `keycounter:warm_cache`.');
            }
        }

        $elapsed = round(microtime(true) - $start, 3);
        $scope = $full ? 'tabla completa' : "últimos {$windowDays} días";
        $mode = $force ? 'BORRADO' : 'DRY-RUN (no se ha borrado nada)';

        $message = "[keycounter:remove_duplicate] tabla={$table} alcance={$scope} modo={$mode} "
            ."duplicados_detectados={$found} filas_borradas={$deleted} tiempo={$elapsed}s";

        $this->info($message);
        Log::info($message);

        // Solo en el dry-run de ventana: cuántos duplicados quedan fuera de
        // ella (históricos, previos a esta versión) y por tanto la operación
        // normal semanal nunca tocaría. Se salta en --force y en --full
        // porque en ambos casos ya carece de sentido (o ya se está mirando
        // la tabla completa, o ya se ha confirmado y no hace falta seguir
        // pagando el escaneo completo cada semana).
        if (! $force && ! $full) {
            $outside = (int) DB::selectOne("
                SELECT COUNT(*) AS total FROM (
                    SELECT id, created_at,
                           ROW_NUMBER() OVER (PARTITION BY {$groupByCols} ORDER BY id) AS rn
                    FROM {$table}
                ) t
                WHERE rn > 1 AND created_at < now() - interval '{$windowDays} days'
            ")->total;

            $diagnostic = "[keycounter:remove_duplicate] tabla={$table} "
                ."diagnostico_duplicados_historicos_fuera_de_ventana={$outside}";

            $this->info($diagnostic);
            Log::info($diagnostic);
        }
    }

    /**
     * Los meses (año, mes) donde hay filas que este borrado se va a llevar.
     *
     * Va por `created_at` porque es la columna con la que se indexan las
     * gráficas cacheadas, no por `start_at`.
     *
     * @return list<array{int, int}>
     */
    private function affectedPeriods(string $table, string $groupByCols, string $windowClause): array
    {
        $rows = DB::select("
            SELECT DISTINCT
                   EXTRACT(YEAR FROM created_at)::int AS year,
                   EXTRACT(MONTH FROM created_at)::int AS month
            FROM (
                SELECT created_at, ROW_NUMBER() OVER (PARTITION BY {$groupByCols} ORDER BY id) AS rn
                FROM {$table}
                {$windowClause}
            ) t
            WHERE rn > 1 AND created_at IS NOT NULL
        ");

        return array_map(
            static fn (object $row): array => [(int) $row->year, (int) $row->month],
            $rows,
        );
    }
}
