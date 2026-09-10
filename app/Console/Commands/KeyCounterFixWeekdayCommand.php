<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\KeyCounterWeekdayEnum;
use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pasa a «0 = lunes» las rachas antiguas, que están en la convención contraria.
 *
 * **El hallazgo.** Comprobado sobre 1,3 millones de filas reales de teclado:
 *
 * | Periodo | `weekday` sigue |
 * |---|---|
 * | 2013-01 … 2019-12 | la convención de Carbon: **0 = domingo**, al 100 % |
 * | 2020-02 … hoy | la del cliente: **0 = lunes**, en el ~95 % |
 *
 * El 5 % que no cuadra en el tramo moderno son las rachas que cruzan la
 * medianoche: `start_at` se guarda en UTC y el cliente calcula el día en hora
 * local, así que una racha de la madrugada aparece en UTC como del día
 * anterior. Ese dato **no está mal**, y por eso el comando no lo toca.
 *
 * O sea: el cliente cambió de convención y la plataforma nunca se enteró. Las
 * dos poblaciones conviven en la misma columna, y cualquier gráfica o filtro
 * por día de la semana que abarque ambas épocas mezcla peras con manzanas.
 *
 * **Sale en seco por defecto.** Esto reescribe datos históricos que no se
 * pueden reconstruir si se hace mal, así que hay que pedir la escritura
 * expresamente con `--write`. Sin ella sólo cuenta y enseña una muestra.
 *
 *     php artisan keycounter:fix_weekday                  # sólo mira
 *     php artisan keycounter:fix_weekday --write          # escribe
 *     php artisan keycounter:fix_weekday --until=2020-01-01
 */
class KeyCounterFixWeekdayCommand extends Command
{
    protected $signature = 'keycounter:fix_weekday
        {--write : Escribir de verdad. Sin esto sólo cuenta lo que cambiaría}
        {--until= : Sólo las rachas anteriores a esta fecha (por defecto, el cambio de convención)}';

    protected $description = 'Normaliza `weekday` a 0=lunes en las rachas anteriores al cambio de convención del cliente';

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
        $limit = (string) ($this->option('until') ?: KeyCounterWeekdayEnum::CONVENTION_CHANGE_DATE);
        $write = (bool) $this->option('write');

        $this->line("▶ Rachas con `start_at` anterior a {$limit}.");

        if (! $write) {
            $this->warn('Modo seco: no se escribe nada. Añade --write para aplicarlo.');
        }

        $total = 0;

        foreach (self::TABLES as $table => $label) {
            $total += $this->process($table, $label, $limit, $write);
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
    private function process(string $table, string $label, string $limit, bool $write): int
    {
        // Sólo las que hoy cuadran con la convención de Carbon: si una fila ya
        // está en la buena, o no cuadra con ninguna de las dos —el caso de las
        // rachas que cruzan la medianoche—, se deja como está. Convertir a
        // ciegas todo lo anterior a la fecha estropearía justamente esas.
        $condition = 'start_at IS NOT NULL
            AND start_at < ?
            AND weekday = EXTRACT(DOW FROM start_at)::int
            AND weekday <> EXTRACT(ISODOW FROM start_at)::int - 1';

        $count = (int) DB::table($table)
            ->whereRaw($condition, [$limit])
            ->count();

        $this->line("  · {$label} ({$table}): {$count}");

        if ($count === 0) {
            return 0;
        }

        if (! $write) {
            $this->showSample($table, $condition, $limit);

            return $count;
        }

        // Qué meses se tocan, **antes** de tocarlos: después la condición ya no
        // los encuentra.
        $periods = $this->affectedPeriods($table, $condition, $limit);

        // Una sola sentencia: son cientos de miles de filas y recorrerlas con
        // Eloquent sería tan lento como innecesario. El valor nuevo sale de la
        // propia fecha, así que no hace falta traerse nada.
        DB::table($table)
            ->whereRaw($condition, [$limit])
            ->update([
                'weekday' => DB::raw('EXTRACT(ISODOW FROM start_at)::int - 1'),
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
    private function affectedPeriods(string $table, string $condition, string $limit): array
    {
        $rows = DB::table($table)
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM created_at)::int AS year, EXTRACT(MONTH FROM created_at)::int AS month')
            ->whereRaw($condition, [$limit])
            ->whereNotNull('created_at')
            ->get();

        return $rows
            ->map(fn (object $row): array => [(int) $row->year, (int) $row->month])
            ->all();
    }

    /**
     * Cinco filas de ejemplo, para poder comprobar el cambio antes de hacerlo.
     */
    private function showSample(string $table, string $condition, string $limit): void
    {
        $rows = DB::table($table)
            ->selectRaw("id, start_at, weekday AS current_value, EXTRACT(ISODOW FROM start_at)::int - 1 AS new_value, to_char(start_at, 'Dy') AS weekday_name")
            ->whereRaw($condition, [$limit])
            ->limit(5)
            ->get();

        foreach ($rows as $row) {
            $current = KeyCounterWeekdayEnum::labelFor((int) $row->current_value);
            $new = KeyCounterWeekdayEnum::labelFor((int) $row->new_value);

            $this->line("      #{$row->id} {$row->start_at} ({$row->weekday_name}): {$row->current_value} «{$current}» → {$row->new_value} «{$new}»");
        }
    }
}
