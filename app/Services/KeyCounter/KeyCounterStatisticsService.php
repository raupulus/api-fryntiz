<?php

declare(strict_types=1);

namespace App\Services\KeyCounter;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Todo lo que la página de KeyCounter enseña, calculado en un solo sitio.
 *
 * Antes vivía dentro del controlador, en closures pasadas a `Cache::remember()`.
 * Mientras lo único que calculaba era el visitante daba igual, pero ahora hay
 * un segundo cliente —`keycounter:warm_cache --live`, que reescribe lo mismo
 * cada hora desde el planificador— y dos copias del mismo cálculo acaban
 * separándose: la web enseñaría una cosa y el refresco escribiría otra, sin que
 * nada avise.
 *
 * La regla de reparto es la de siempre:
 *
 *  - **`cached*()`**: lo que llama la web. Lee y sólo calcula si falta.
 *  - **`refreshLive()`**: lo que llama el planificador. Recalcula y sobrescribe.
 *  - **`compute*()`**: el cálculo desnudo, sin caché, que usan los dos.
 *
 * Las ventanas y las claves no están aquí: viven en `KeyCounterCache`, que es
 * quien decide qué es un periodo cerrado y cuánto dura cada cosa.
 */
class KeyCounterStatisticsService
{
    /**
     * Primer año con el que se muestran tarjetas de totales anuales.
     */
    public const FIRST_STATS_YEAR = 2013;

    /**
     * Cuántas rachas recientes entran en las tarjetas de resumen.
     */
    private const SUMMARY_RECORDS = 100;

    // ── Lectura: lo que usa la web ───────────────────────────────────────────

    /**
     * Datos de la gráfica de un mes.
     *
     * Lo más caro de la página: agrega todas las rachas del mes por día y por
     * dispositivo. Un mes cerrado sale de la caja fuerte; uno vivo, de lo que
     * dejó escrito el último refresco horario.
     *
     * @return array<string, mixed>
     */
    public function cachedGraph(int $year, int $month): array
    {
        return KeyCounterCache::rememberGraph(
            $year,
            $month,
            fn (): array => $this->computeGraph($year, $month),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedKeyboardSummary(): array
    {
        return KeyCounterCache::rememberLive(
            KeyCounterCache::KEYBOARD_SUMMARY_KEY,
            fn (): array => $this->computeKeyboardSummary(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedMouseSummary(): array
    {
        return KeyCounterCache::rememberLive(
            KeyCounterCache::MOUSE_SUMMARY_KEY,
            fn (): array => $this->computeMouseSummary(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedWidgets(): array
    {
        return KeyCounterCache::rememberLive(
            KeyCounterCache::WIDGETS_KEY,
            fn (): array => $this->computeWidgets(),
        );
    }

    /**
     * Total de pulsaciones por año, de `FIRST_STATS_YEAR` al año en curso.
     *
     * Los años cerrados se guardan para siempre —`created_at` sólo avanza, así
     * que no van a cambiar— y sólo el año en curso es un dato vivo.
     *
     * @return Collection<int, stdClass> Objetos con `year` y `total`.
     */
    public function cachedYearlyTotals(?int $currentYear = null): Collection
    {
        $currentYear ??= (int) date('Y');

        return collect(range(self::FIRST_STATS_YEAR, $currentYear))
            ->mapWithKeys(fn (int $year): array => [
                $year => KeyCounterCache::rememberYearTotal(
                    $year,
                    $currentYear,
                    fn (): int => $this->computeYearTotal($year),
                ),
            ])
            ->map(fn (int $total, int $year): stdClass => $this->yearTotalRow($year, $total))
            ->values()
            ->sortByDesc('year')
            ->values();
    }

    /**
     * Una fila de la tabla de totales anuales, tal y como la espera la vista.
     */
    private function yearTotalRow(int $year, int $total): stdClass
    {
        return (object) ['year' => $year, 'total' => $total];
    }

    // ── Escritura: lo que usa el planificador ────────────────────────────────

    /**
     * Recalcula y reescribe todo lo vivo, sin esperar a que nadie lo pida.
     *
     * Es lo que convierte la ventana de una hora en algo que el visitante no
     * paga nunca: cuando entra, lo que quiere ya está escrito. Sin esto la
     * caché es perezosa y el primer visitante de cada hora se come el cálculo
     * entero —que sobre trece años de rachas es más de un segundo—, con lo que
     * la web sigue tardando justo lo que la caché venía a evitar.
     *
     * Reescribe el mes en curso y el anterior (los dos periodos abiertos), los
     * dos resúmenes, los widgets y el total del año en curso. Todo lo que la
     * página enseña queda, como mucho, a una hora de la realidad.
     *
     * @return list<string> Las claves reescritas, para que el comando las liste.
     */
    public function refreshLive(): array
    {
        $refreshed = [];

        foreach (KeyCounterCache::openPeriods() as [$year, $month]) {
            KeyCounterCache::putGraph($year, $month, $this->computeGraph($year, $month));
            $refreshed[] = KeyCounterCache::graphCacheKey($year, $month);
        }

        KeyCounterCache::putLive(
            KeyCounterCache::KEYBOARD_SUMMARY_KEY,
            $this->computeKeyboardSummary(),
        );
        $refreshed[] = KeyCounterCache::KEYBOARD_SUMMARY_KEY;

        KeyCounterCache::putLive(
            KeyCounterCache::MOUSE_SUMMARY_KEY,
            $this->computeMouseSummary(),
        );
        $refreshed[] = KeyCounterCache::MOUSE_SUMMARY_KEY;

        KeyCounterCache::putLive(
            KeyCounterCache::WIDGETS_KEY,
            $this->computeWidgets(),
        );
        $refreshed[] = KeyCounterCache::WIDGETS_KEY;

        $currentYear = (int) date('Y');

        KeyCounterCache::putLive(
            KeyCounterCache::yearTotalCacheKey($currentYear),
            $this->computeYearTotal($currentYear),
        );
        $refreshed[] = KeyCounterCache::yearTotalCacheKey($currentYear);

        return $refreshed;
    }

    // ── Cálculo desnudo ──────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function computeGraph(int $year, int $month): array
    {
        return Keyboard::getStatisticsPreparedToGraphics($month, $year);
    }

    /**
     * Resumen de las últimas rachas de teclado.
     *
     * @return array<string, mixed>
     */
    public function computeKeyboardSummary(): array
    {
        $records = Keyboard::whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('pulsations', '>', 0)
            ->orderByDesc('created_at')
            ->take(self::SUMMARY_RECORDS)
            ->get();

        return [
            'total_records' => $records->count(),
            'avg_pulsations' => round($records->avg('pulsations') ?? 0, 2),
            'avg_pulsations_per_minute' => round($records->avg('pulsation_average') ?? 0, 2),
            'avg_score' => round($records->avg('score') ?? 0, 2),
            'avg_pulsations_special_keys' => round($records->avg('pulsations_special_keys') ?? 0, 2),
            'max_pulsations' => $records->max('pulsations') ?? 0,
            'total_pulsations' => $records->sum('pulsations') ?? 0,
            'total_pulsations_special_keys' => $records->sum('pulsations_special_keys') ?? 0,
            'period_start' => $records->min('created_at')?->format('d/m/Y H:i') ?? 'N/A',
            'period_end' => $records->max('created_at')?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    /**
     * Resumen de las últimas rachas de ratón.
     *
     * @return array<string, mixed>
     */
    public function computeMouseSummary(): array
    {
        $records = Mouse::whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('total_clicks', '>', 0)
            ->orderByDesc('created_at')
            ->take(self::SUMMARY_RECORDS)
            ->get();

        return [
            'total_records' => $records->count(),
            'avg_clicks' => round($records->avg('total_clicks') ?? 0, 2),
            'avg_clicks_per_minute' => round($records->avg('clicks_average') ?? 0, 2),
            'max_clicks' => $records->max('total_clicks') ?? 0,
            'total_clicks' => $records->sum('total_clicks') ?? 0,
            'period_start' => $records->min('created_at')?->format('d/m/Y H:i') ?? 'N/A',
            'period_end' => $records->max('created_at')?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    /**
     * Widgets: total global, mejor mes, mejor día, mejor hora y totales por
     * dispositivo.
     *
     * Son agregaciones sobre la tabla entera, así que es lo más caro que hay
     * aquí después de la gráfica. Por eso las paga el refresco horario y no el
     * visitante.
     *
     * @return array<string, mixed>
     */
    public function computeWidgets(): array
    {
        $totalGlobal = Keyboard::sum('pulsations');

        $topMonth = Keyboard::selectRaw('EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, SUM(pulsations) as total')
            ->groupByRaw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)')
            ->orderByDesc('total')
            ->first();

        // Día concreto (calendario) con más pulsaciones acumuladas.
        $topDay = Keyboard::selectRaw('DATE(created_at) as day, SUM(pulsations) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('total')
            ->first();

        // Hora del día (0-23) con más pulsaciones acumuladas. Se agrega en la
        // hora tal cual se guarda (UTC); la vista la convierte a la hora local
        // del navegador con JS antes de mostrarla.
        $topHour = Keyboard::selectRaw('EXTRACT(HOUR FROM created_at) as hour, SUM(pulsations) as total')
            ->groupByRaw('EXTRACT(HOUR FROM created_at)')
            ->orderByDesc('total')
            ->first();

        // Totales de pulsaciones por cada dispositivo, resolviendo los nombres
        // en una única consulta para no caer en un N+1.
        //
        // `pluck()` y no `get()`: lo que sale de un `SUM(...) as total` no es
        // una columna del modelo, y leerlo como propiedad de un `Keyboard` es
        // justo el tipo de acceso que no se puede comprobar. Aquí queda un mapa
        // «id de dispositivo → total», que es lo único que hace falta.
        $totalsByDeviceRaw = Keyboard::selectRaw('hardware_device_id, SUM(pulsations) as total')
            ->whereNotNull('hardware_device_id')
            ->groupBy('hardware_device_id')
            ->orderByDesc('total')
            ->pluck('total', 'hardware_device_id');

        $devices = HardwareDevice::whereIn('id', $totalsByDeviceRaw->keys())
            ->get(['id', 'name', 'name_friendly'])
            ->keyBy('id');

        $deviceName = function (int $deviceId) use ($devices): string {
            $device = $devices->get($deviceId);

            if ($device === null) {
                return "Device #{$deviceId}";
            }

            return $device->name_friendly ?? $device->name ?? "Device #{$deviceId}";
        };

        $totalsByDevice = $totalsByDeviceRaw
            ->map(fn (mixed $total, int|string $deviceId): object => (object) [
                'hardware_device_id' => (int) $deviceId,
                'name' => $deviceName((int) $deviceId),
                'total' => $total,
            ])
            ->values();

        return [
            'total_global' => $totalGlobal,
            'top_month' => $topMonth,
            'top_day' => $topDay,
            'top_hour' => $topHour,
            // El dispositivo con más pulsaciones sale de la lista ya ordenada,
            // sin repetir la consulta agregada. La vista no le dedica una
            // tarjeta propia: marca la suya con el distintivo.
            'top_device' => $totalsByDevice->first(),
            'totals_by_device' => $totalsByDevice,
        ];
    }

    /**
     * Total de pulsaciones de un año.
     */
    public function computeYearTotal(int $year): int
    {
        return (int) Keyboard::whereYear('created_at', $year)->sum('pulsations');
    }

    /**
     * Estadísticas agregadas del mes elegido, a partir de los datos que ya trae
     * la gráfica: no repite ninguna consulta.
     *
     * @param  array<string, mixed>  $keyboardStatistics
     * @return array<string, float|int>
     */
    public function monthSummary(array $keyboardStatistics): array
    {
        /** @var Collection $data */
        $data = $keyboardStatistics['data'];
        $spurts = $keyboardStatistics['period_count'];
        $totalPulsations = $keyboardStatistics['period_total_pulsations'];
        $totalScore = $data->sum('total_score');
        $totalSpecialKeys = $data->sum('total_pulsations_special_keys');
        $totalDurationMinutes = $data->sum('duration') / 60;

        return [
            'total_pulsations' => $totalPulsations,
            'total_score' => $totalScore,
            'avg_pulsations' => $spurts > 0 ? round($totalPulsations / $spurts, 2) : 0,
            'avg_special_keys' => $spurts > 0 ? round($totalSpecialKeys / $spurts, 2) : 0,
            'pulsations_per_minute' => $totalDurationMinutes > 0 ? round($totalPulsations / $totalDurationMinutes, 2) : 0,
            'avg_score' => $spurts > 0 ? round($totalScore / $spurts, 2) : 0,
        ];
    }
}
