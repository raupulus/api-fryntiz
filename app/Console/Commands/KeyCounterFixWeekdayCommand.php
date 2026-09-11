<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\KeyCounterWeekdayEnum;
use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula `weekday` desde `start_at`: **0 siempre es domingo**
 * (`Carbon::dayOfWeek`).
 *
 * No hay dos convenciones que convivir ni una fecha de corte que respetar:
 * cualquier fila cuyo `weekday` no coincida con el día real de `start_at` se
 * corrige, sea de cuando sea. El valor nuevo sale siempre de la propia fecha,
 * nunca del que trajera la fila.
 *
 * **Sale en seco por defecto.** Esto reescribe datos históricos que no se
 * pueden reconstruir si se hace mal, así que hay que pedir la escritura
 * expresamente con `--write`. Sin ella sólo cuenta y enseña una muestra.
 *
 *     php artisan keycounter:fix_weekday                  # sólo mira
 *     php artisan keycounter:fix_weekday --write          # escribe
 */
class KeyCounterFixWeekdayCommand extends Command
{
    protected $signature = 'keycounter:fix_weekday
        {--write : Escribir de verdad. Sin esto sólo cuenta lo que cambiaría}';

    protected $description = 'Recalcula `weekday` desde `start_at` para que 0 sea siempre domingo';

    /**
     * Las dos tablas, con el modelo que las representa.
     *
     * @var array<string, string>
     */
    private const TABLES = [
        'keycounter_keyboard' => 'Teclado',
        'keycounter_mouse' => 'Ratón',
    ];

    public function handle(): int
    {
        $write = (bool) $this->option('write');

        if (! $write) {
            $this->warn('Modo seco: no se escribe nada. Añade --write para aplicarlo.');
        }

        $total = 0;

        foreach (self::TABLES as $table => $label) {
            $total += $this->process($table, $label, $write);
        }

        if ($total === 0) {
            $this->info('No hay nada que normalizar.');

            return self::SUCCESS;
        }

        if ($write) {
            $this->info("Normalizadas {$total} rachas.");
        } else {
            $this->info("Se normalizarían {$total} rachas. Repite con --write para hacerlo.");
        }

        return self::SUCCESS;
    }

    /**
     * @return int Rachas afectadas.
     */
    private function process(string $table, string $label, bool $write): int
    {
        $condition = 'start_at IS NOT NULL AND weekday <> EXTRACT(DOW FROM start_at)::int';

        $count = (int) DB::table($table)->whereRaw($condition)->count();

        $this->line("  · {$label} ({$table}): {$count}");

        if ($count === 0) {
            return 0;
        }

        if (! $write) {
            $this->showSample($table, $condition);

            return $count;
        }

        // Qué meses se tocan, **antes** de tocarlos: después la condición ya no
        // los encuentra.
        $periods = $this->affectedPeriods($table, $condition);

        // Una sola sentencia: son cientos de miles de filas y recorrerlas con
        // Eloquent sería tan lento como innecesario. El valor nuevo sale de la
        // propia fecha, así que no hace falta traerse nada.
        DB::table($table)
            ->whereRaw($condition)
            ->update([
                'weekday' => DB::raw('EXTRACT(DOW FROM start_at)::int'),
            ]);

        // Esto reescribe rachas de meses **cerrados**, y esos están cacheados
        // para siempre. Hoy `weekday` no entra en ninguna de las cifras que se
        // guardan —la gráfica agrupa por día del mes y los widgets por fecha—,
        // así que en rigor no cambiaría nada de lo cacheado; se invalida
        // igualmente porque el día que alguien añada un corte por día de la
        // semana, nadie va a acordarse de volver aquí. Es barato: lo rehace el
        // pase diario de `keycounter:warm_cache`.
        if ($table === 'keycounter_keyboard' && $periods !== []) {
            KeyCounterCache::forgetPeriods($periods);

            $this->line('      Caché olvidada de '.count($periods).' meses. '
                .'Para no dejársela al primer visitante: `php artisan keycounter:warm_cache`.');
        }

        return $count;
    }

    /**
     * Los meses (año, mes) de las rachas que este comando va a reescribir.
     *
     * Va por `created_at` porque es la columna con la que se indexan las
     * gráficas cacheadas, no por `start_at` —que es la que decide qué filas se
     * tocan.
     *
     * @return list<array{int, int}>
     */
    private function affectedPeriods(string $table, string $condition): array
    {
        $rows = DB::table($table)
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM created_at)::int AS year, EXTRACT(MONTH FROM created_at)::int AS month')
            ->whereRaw($condition)
            ->whereNotNull('created_at')
            ->get();

        return $rows
            ->map(fn (object $row): array => [(int) $row->year, (int) $row->month])
            ->all();
    }

    /**
     * Cinco filas de ejemplo, para poder comprobar el cambio antes de hacerlo.
     */
    private function showSample(string $table, string $condition): void
    {
        $rows = DB::table($table)
            ->selectRaw("id, start_at, weekday AS current_value, EXTRACT(DOW FROM start_at)::int AS new_value, to_char(start_at, 'Dy') AS weekday_name")
            ->whereRaw($condition)
            ->limit(5)
            ->get();

        foreach ($rows as $row) {
            $current = KeyCounterWeekdayEnum::labelFor((int) $row->current_value);
            $new = KeyCounterWeekdayEnum::labelFor((int) $row->new_value);

            $this->line("      #{$row->id} {$row->start_at} ({$row->weekday_name}): {$row->current_value} «{$current}» → {$row->new_value} «{$new}»");
        }
    }
}
