<?php

declare(strict_types=1);

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use JsonHelper;

/**
 * Class KeyCounterController
 */
class KeyCounterController extends Controller
{
    /**
     * Primer año con el que se muestran tarjetas de totales anuales.
     */
    private const FIRST_STATS_YEAR = 2020;

    /**
     * Vista con las estadísticas generales para el contador de pulsaciones
     * y clicks a modo de ejemplo o muestra.
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        $month = (int) ($request->get('month') ?? date('m'));
        $year = (int) ($request->get('year') ?? date('Y'));

        $statistics = Keyboard::getStatisticsPreparedToGraphics($month, $year);
        $monthSummary = $this->buildMonthSummary($statistics['keyboard_statistics']);

        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo',
            'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre',
            'Noviembre', 'Diciembre'];

        // Resumen Keyboard (caché 1 hora)
        $keyboardSummary = Cache::remember('keycounter:keyboard:summary', 3600, function () {
            $records = Keyboard::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('pulsations', '>', 0)
                ->orderByDesc('created_at')
                ->take(100)
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
        });

        // Resumen Mouse (caché 1 hora)
        $mouseSummary = Cache::remember('keycounter:mouse:summary', 3600, function () {
            $records = Mouse::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('total_clicks', '>', 0)
                ->orderByDesc('created_at')
                ->take(100)
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
        });

        // Totales por año (cada año cerrado se cachea para siempre porque nunca
        // volverá a recibir registros nuevos; solo el año en curso se recalcula).
        $totalsByYear = $this->getYearlyPulsationTotals((int) date('Y'));
        $topYear = $totalsByYear->sortByDesc('total')->first();

        // Widgets estadísticos (caché 24 horas)
        $widgets = Cache::remember('keycounter:widgets', 86400, function () {
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

            // Hora del día (0-23) con más pulsaciones acumuladas. Se agrega
            // en la hora tal cual se guarda (UTC); la vista la convierte a
            // la hora local del navegador con JS antes de mostrarla.
            $topHour = Keyboard::selectRaw('EXTRACT(HOUR FROM created_at) as hour, SUM(pulsations) as total')
                ->groupByRaw('EXTRACT(HOUR FROM created_at)')
                ->orderByDesc('total')
                ->first();

            // Dispositivo con más pulsaciones (top_device)
            $topDevice = Keyboard::selectRaw('hardware_device_id, SUM(pulsations) as total')
                ->whereNotNull('hardware_device_id')
                ->groupBy('hardware_device_id')
                ->orderByDesc('total')
                ->first();

            // Totales de pulsaciones por cada dispositivo (totals_by_device),
            // evitando N+1 al resolver los nombres en una única consulta.
            $totalsByDeviceRaw = Keyboard::selectRaw('hardware_device_id, SUM(pulsations) as total')
                ->whereNotNull('hardware_device_id')
                ->groupBy('hardware_device_id')
                ->orderByDesc('total')
                ->get();

            $devices = HardwareDevice::whereIn('id', $totalsByDeviceRaw->pluck('hardware_device_id'))
                ->get(['id', 'name', 'name_friendly'])
                ->keyBy('id');

            $deviceName = function (int $deviceId) use ($devices) {
                $device = $devices->get($deviceId);

                return $device?->name_friendly ?? $device?->name ?? "Device #{$deviceId}";
            };

            $totalsByDevice = $totalsByDeviceRaw->map(fn ($row) => (object) [
                'hardware_device_id' => $row->hardware_device_id,
                'name' => $deviceName((int) $row->hardware_device_id),
                'total' => $row->total,
            ]);

            return [
                'total_global' => $totalGlobal,
                'top_month' => $topMonth,
                'top_day' => $topDay,
                'top_hour' => $topHour,
                'top_device' => $topDevice ? (object) [
                    'hardware_device_id' => $topDevice->hardware_device_id,
                    'name' => $deviceName((int) $topDevice->hardware_device_id),
                    'total' => $topDevice->total,
                ] : null,
                'totals_by_device' => $totalsByDevice,
            ];
        });

        $widgets['top_year'] = $topYear && $topYear->total > 0 ? $topYear : null;
        $widgets['totals_by_year'] = $totalsByYear;

        return view('keycounter.index')->with([
            'month' => $month,
            'year' => $year,
            'labelsString' => $statistics['labelsString'],
            'datasetJson' => $statistics['datasetJson'],
            'keyboard_statistics' => $statistics['keyboard_statistics'],
            'monthSummary' => $monthSummary,
            'months' => $months,
            'keyboardSummary' => $keyboardSummary,
            'mouseSummary' => $mouseSummary,
            'widgets' => $widgets,
        ]);
    }

    /**
     * Calcula estadísticas agregadas del mes/año seleccionado a partir de los
     * datos ya obtenidos para la gráfica (evita repetir consultas).
     */
    private function buildMonthSummary(array $keyboardStatistics): array
    {
        /** @var Collection $data */
        $data = $keyboardStatistics['data'];
        $gusts = $keyboardStatistics['period_count'];
        $totalPulsations = $keyboardStatistics['period_total_pulsations'];
        $totalScore = $data->sum('total_score');
        $totalSpecialKeys = $data->sum('total_pulsations_special_keys');
        $totalDurationMinutes = $data->sum('duration') / 60;

        return [
            'total_pulsations' => $totalPulsations,
            'total_score' => $totalScore,
            'avg_pulsations' => $gusts > 0 ? round($totalPulsations / $gusts, 2) : 0,
            'avg_special_keys' => $gusts > 0 ? round($totalSpecialKeys / $gusts, 2) : 0,
            'pulsations_per_minute' => $totalDurationMinutes > 0 ? round($totalPulsations / $totalDurationMinutes, 2) : 0,
            'avg_score' => $gusts > 0 ? round($totalScore / $gusts, 2) : 0,
        ];
    }

    /**
     * Devuelve el total de pulsaciones por año desde self::FIRST_STATS_YEAR
     * hasta el año actual (ambos incluidos), rellenando con 0 los años sin
     * registros.
     *
     * Los años ya cerrados se cachean para siempre: nunca van a recibir
     * registros nuevos (created_at siempre avanza), así que no tiene sentido
     * volver a consultarlos. Solo el año en curso usa una caché de corta
     * duración que además se invalida al guardar un registro nuevo (ver
     * KeyCounterService).
     */
    private function getYearlyPulsationTotals(int $currentYear): Collection
    {
        $years = range(self::FIRST_STATS_YEAR, $currentYear);

        return collect($years)
            ->mapWithKeys(function (int $year) use ($currentYear) {
                $key = "keycounter:year_total:{$year}";
                $query = fn () => (int) Keyboard::whereYear('created_at', $year)->sum('pulsations');

                $total = $year < $currentYear
                    ? Cache::rememberForever($key, $query)
                    : Cache::remember($key, 3600, $query);

                return [$year => $total];
            })
            ->map(fn (int $total, int $year) => (object) ['year' => $year, 'total' => $total])
            ->values()
            ->sortByDesc('year')
            ->values();
    }

    /* Ajax */

    /**
     * Devuelve los datos en json para las pulsaciones de teclado.
     *
     * ⚠️ Este método **no tiene ruta registrada** (`routes/keycounter/web.php`
     * sólo declara `index`). Se conserva porque la vista de KeyCounter va a
     * necesitarlo cuando se le añada el selector de mes; hasta entonces no lo
     * llama nadie.
     *
     * Hasta la revisión de 2026-09-02 hacía `response()->json(JsonHelper::success($data))`,
     * es decir, metía un `JsonResponse` dentro de otro. El resultado no era el
     * envelope sino el objeto de respuesta serializado:
     * `{"headers":{},"original":{...},"exception":null}`. Como el método no
     * estaba enrutado, nunca dio la cara.
     */
    public function getKeyboardDataAjax(Request $request): JsonResponse
    {
        $month = (int) ($request->get('month') ?? date('m'));
        $year = (int) ($request->get('year') ?? date('Y'));

        $statistics = Keyboard::getStatisticsPreparedToGraphics($month, $year);

        return JsonHelper::success([
            'month' => $month,
            'year' => $year,
            'labelsString' => $statistics['labelsString'],
            'datasetJson' => $statistics['datasetJson'],
            'keyboard_statistics' => $statistics['keyboard_statistics'],
        ]);
    }

    public function getMouseDataAjax()
    {
        // TODO
    }
}
