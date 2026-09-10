<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\KeyCounter\KeyCounterStatisticsService;
use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function count;

/**
 * Deja las estadísticas de KeyCounter calculadas antes de que nadie las pida.
 *
 * Tiene dos pasadas, y hacen cosas distintas:
 *
 * **`--live` (cada hora).** Reescribe lo que está vivo: el mes en curso, el
 * anterior, los dos resúmenes, los widgets y el total del año en curso. Es lo
 * que hace que la ventana de una hora sea gratis para el visitante: cuando
 * entra, ya está todo escrito. Sin esta pasada la caché es perezosa y el primer
 * visitante de cada hora paga el cálculo entero —más de un segundo sobre el
 * volcado real—, con lo que la web sigue tardando justo lo que la caché venía a
 * evitar.
 *
 * **Sin opciones (cada día).** Precalcula los meses **cerrados**, que se
 * guardan para siempre. Cada mes se paga una sola vez en la vida, pero lo
 * pagaba **quien entrara primero**, y con trece años de datos son unos 150
 * meses esperando a que alguien los estrene. Va a diario y no semanal porque el
 * mes que acaba de cerrarse tiene que entrar en la caja fuerte cuanto antes: en
 * semanal se quedaba hasta seis días fuera, con el coste a cargo del visitante.
 *
 * Ninguna de las dos sustituye a la caché: la rellenan.
 */
class KeyCounterWarmCacheCommand extends Command
{
    protected $signature = 'keycounter:warm_cache
        {--live : Refresca lo vivo (mes en curso y anterior, resúmenes, widgets y total del año) en vez de los meses cerrados}
        {--months= : Cuántos meses hacia atrás. Sin esto, todos los que tengan datos}
        {--force : Recalcula también los meses cerrados que ya estuvieran cacheados}';

    protected $description = 'Precalcula las estadísticas de KeyCounter para que no las pague quien entra en la web';

    public function __construct(private KeyCounterStatisticsService $statistics)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return $this->option('live')
            ? $this->warmLive()
            : $this->warmClosedMonths();
    }

    /**
     * Reescribe lo vivo. Siempre, esté ya cacheado o no: de eso se trata.
     */
    private function warmLive(): int
    {
        $this->line('▶ Refrescando las estadísticas vivas.');

        $refreshed = $this->statistics->refreshLive();

        foreach ($refreshed as $key) {
            $this->line("  · {$key}");
        }

        $this->info(count($refreshed).' claves reescritas. Antigüedad máxima de la web: 1 h.');

        return self::SUCCESS;
    }

    /**
     * Precalcula los meses cerrados que se guardan para siempre.
     */
    private function warmClosedMonths(): int
    {
        $months = $this->monthsWithData();

        if ($months === []) {
            $this->info('No hay rachas registradas: nada que precalcular.');

            return self::SUCCESS;
        }

        $limit = $this->option('months') !== null
            ? max(1, (int) $this->option('months'))
            : null;

        if ($limit !== null) {
            $months = array_slice($months, -$limit);
        }

        $force = (bool) $this->option('force');

        $this->line('▶ Precalculando '.count($months).' meses.');

        $bar = $this->output->createProgressBar(count($months));
        $bar->start();
        $processed = 0;

        foreach ($months as [$year, $month]) {
            // Sólo los cerrados: los abiertos son cosa de `--live`, que los
            // reescribe cada hora.
            if (KeyCounterCache::isPeriodClosed($year, $month)) {
                if ($force) {
                    // Para después de tocar rachas históricas
                    // (`remove_duplicate --full`, `fix_weekday --write`): lo que
                    // ya estaba guardado «para siempre» hay que reescribirlo,
                    // no respetarlo.
                    KeyCounterCache::putGraph($year, $month, $this->statistics->computeGraph($year, $month));
                } else {
                    KeyCounterCache::rememberGraph(
                        $year,
                        $month,
                        fn (): array => $this->statistics->computeGraph($year, $month),
                    );
                }

                $processed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info($processed === 1 ? 'Listo 1 mes.' : "Listos {$processed} meses.");

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
    private function monthsWithData(): array
    {
        $rows = DB::table('keycounter_keyboard')
            ->selectRaw('EXTRACT(YEAR FROM created_at)::int AS year, EXTRACT(MONTH FROM created_at)::int AS month')
            ->whereNotNull('created_at')
            ->groupByRaw('1, 2')
            ->orderByRaw('1, 2')
            ->get();

        return $rows
            ->map(fn (object $row): array => [(int) $row->year, (int) $row->month])
            ->all();
    }
}
