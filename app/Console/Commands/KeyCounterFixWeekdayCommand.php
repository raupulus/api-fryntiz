<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\KeyCounterWeekdayEnum;
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
    private const TABLAS = [
        'keycounter_keyboard' => 'Teclado',
        'keycounter_mouse' => 'Ratón',
    ];

    public function handle(): int
    {
        $limite = (string) ($this->option('until') ?: KeyCounterWeekdayEnum::CAMBIO_DE_CONVENCION);
        $escribir = (bool) $this->option('write');

        $this->line("▶ Rachas con `start_at` anterior a {$limite}.");

        if (! $escribir) {
            $this->warn('Modo seco: no se escribe nada. Añade --write para aplicarlo.');
        }

        $total = 0;

        foreach (self::TABLAS as $tabla => $etiqueta) {
            $total += $this->procesar($tabla, $etiqueta, $limite, $escribir);
        }

        if ($total === 0) {
            $this->info('No hay nada que normalizar.');

            return self::SUCCESS;
        }

        if ($escribir) {
            $this->info("Normalizadas {$total} rachas.");
        } else {
            $this->info("Se normalizarían {$total} rachas. Repite con --write para hacerlo.");
        }

        return self::SUCCESS;
    }

    /**
     * @return int Rachas afectadas.
     */
    private function procesar(string $tabla, string $etiqueta, string $limite, bool $escribir): int
    {
        // Sólo las que hoy cuadran con la convención de Carbon: si una fila ya
        // está en la buena, o no cuadra con ninguna de las dos —el caso de las
        // rachas que cruzan la medianoche—, se deja como está. Convertir a
        // ciegas todo lo anterior a la fecha estropearía justamente esas.
        $condicion = 'start_at IS NOT NULL
            AND start_at < ?
            AND weekday = EXTRACT(DOW FROM start_at)::int
            AND weekday <> EXTRACT(ISODOW FROM start_at)::int - 1';

        $cuantas = (int) DB::table($tabla)
            ->whereRaw($condicion, [$limite])
            ->count();

        $this->line("  · {$etiqueta} ({$tabla}): {$cuantas}");

        if ($cuantas === 0) {
            return 0;
        }

        if (! $escribir) {
            $this->muestra($tabla, $condicion, $limite);

            return $cuantas;
        }

        // Una sola sentencia: son cientos de miles de filas y recorrerlas con
        // Eloquent sería tan lento como innecesario. El valor nuevo sale de la
        // propia fecha, así que no hace falta traerse nada.
        DB::table($tabla)
            ->whereRaw($condicion, [$limite])
            ->update([
                'weekday' => DB::raw('EXTRACT(ISODOW FROM start_at)::int - 1'),
            ]);

        return $cuantas;
    }

    /**
     * Cinco filas de ejemplo, para poder comprobar el cambio antes de hacerlo.
     */
    private function muestra(string $tabla, string $condicion, string $limite): void
    {
        $filas = DB::table($tabla)
            ->selectRaw("id, start_at, weekday AS ahora, EXTRACT(ISODOW FROM start_at)::int - 1 AS quedaria, to_char(start_at, 'Dy') AS dia")
            ->whereRaw($condicion, [$limite])
            ->limit(5)
            ->get();

        foreach ($filas as $fila) {
            $ahora = KeyCounterWeekdayEnum::etiquetaDe((int) $fila->ahora);
            $quedaria = KeyCounterWeekdayEnum::etiquetaDe((int) $fila->quedaria);

            $this->line("      #{$fila->id} {$fila->start_at} ({$fila->dia}): {$fila->ahora} «{$ahora}» → {$fila->quedaria} «{$quedaria}»");
        }
    }
}
