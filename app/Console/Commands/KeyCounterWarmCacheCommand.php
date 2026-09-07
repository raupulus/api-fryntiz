<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\KeyCounter\Keyboard;
use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function count;

/**
 * Deja las gráficas de KeyCounter calculadas antes de que nadie las pida.
 *
 * Cada mes de la página se calcula agregando todas las rachas de ese mes por
 * día y por dispositivo. Con el mes cacheado para siempre eso se paga una sola
 * vez, pero la paga **quien entra primero**, y con trece años de datos son
 * unos 150 meses esperando a que alguien los estrene.
 *
 * Esto los rellena de madrugada. No sustituye a la caché: la rellena.
 *
 * Sólo toca los meses **cerrados**, que son los que se guardan para siempre; el
 * mes en curso tiene ventana de un cuarto de hora y precalentarlo no serviría
 * de nada.
 */
class KeyCounterWarmCacheCommand extends Command
{
    protected $signature = 'keycounter:warm_cache
        {--months= : Cuántos meses hacia atrás. Sin esto, todos los que tengan datos}';

    protected $description = 'Precalcula las gráficas mensuales de KeyCounter que están cacheadas para siempre';

    public function handle(): int
    {
        $meses = $this->mesesConDatos();

        if ($meses === []) {
            $this->info('No hay rachas registradas: nada que precalcular.');

            return self::SUCCESS;
        }

        $limite = $this->option('months') !== null
            ? max(1, (int) $this->option('months'))
            : null;

        if ($limite !== null) {
            $meses = array_slice($meses, -$limite);
        }

        $this->line('▶ Precalculando '.count($meses).' meses.');

        $barra = $this->output->createProgressBar(count($meses));
        $barra->start();
        $calculados = 0;

        foreach ($meses as [$year, $month]) {
            // Sólo los cerrados: el mes en curso caduca en 15 minutos y
            // calentarlo aquí no le ahorra el trabajo a nadie.
            if (KeyCounterCache::esPeriodoCerrado($year, $month)) {
                KeyCounterCache::recordarGrafica(
                    $year,
                    $month,
                    fn () => Keyboard::getStatisticsPreparedToGraphics($month, $year),
                );

                $calculados++;
            }

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info($calculados === 1 ? 'Listo 1 mes.' : "Listos {$calculados} meses.");

        return self::SUCCESS;
    }

    /**
     * Los meses que tienen alguna racha, del más antiguo al más reciente.
     *
     * Sale de una sola consulta agregada: recorrer año por año preguntando si
     * hay datos costaría más que esto.
     *
     * @return list<array{int, int}>
     */
    private function mesesConDatos(): array
    {
        $filas = DB::table('keycounter_keyboard')
            ->selectRaw('EXTRACT(YEAR FROM created_at)::int AS anio, EXTRACT(MONTH FROM created_at)::int AS mes')
            ->whereNotNull('created_at')
            ->groupByRaw('1, 2')
            ->orderByRaw('1, 2')
            ->get();

        return $filas
            ->map(fn (object $fila): array => [(int) $fila->anio, (int) $fila->mes])
            ->all();
    }
}
