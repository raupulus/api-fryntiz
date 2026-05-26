<?php

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JsonHelper;

/**
 * Class KeyCounterController
 *
 * @package App\Http\Controllers\KeyCounter
 */
class KeyCounterController extends Controller
{
    /**
     * Vista con las estadísticas generales para el contador de pulsaciones
     * y clicks a modo de ejemplo o muestra.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $month = (int) ($request->get('month') ?? date('m'));
        $year = (int) ($request->get('year') ?? date('Y'));

        $statistics = Keyboard::getStatisticsPreparedToGraphics($month, $year);

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
                'max_pulsations' => $records->max('pulsations') ?? 0,
                'total_pulsations' => $records->sum('pulsations') ?? 0,
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

        // Widgets estadísticos (caché 24 horas)
        $widgets = Cache::remember('keycounter:widgets', 86400, function () {
            $totalGlobal = Keyboard::sum('pulsations');

            $topYear = Keyboard::selectRaw("EXTRACT(YEAR FROM created_at) as year, SUM(pulsations) as total")
                ->groupByRaw("EXTRACT(YEAR FROM created_at)")
                ->orderByDesc('total')
                ->first();

            $topMonth = Keyboard::selectRaw("EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, SUM(pulsations) as total")
                ->groupByRaw("EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)")
                ->orderByDesc('total')
                ->first();

            $totalsByYear = Keyboard::selectRaw("EXTRACT(YEAR FROM created_at) as year, SUM(pulsations) as total")
                ->groupByRaw("EXTRACT(YEAR FROM created_at)")
                ->orderByDesc('year')
                ->get();

            // Dispositivo con más pulsaciones (top_device)
            $topDevice = Keyboard::selectRaw('hardware_device_id, SUM(pulsations) as total')
                ->whereNotNull('hardware_device_id')
                ->groupBy('hardware_device_id')
                ->orderByDesc('total')
                ->first();

            $topDeviceName = null;
            if ($topDevice) {
                $device = \App\Models\Hardware\HardwareDevice::find($topDevice->hardware_device_id);
                $topDeviceName = $device?->name_friendly ?? $device?->name ?? "Device #{$topDevice->hardware_device_id}";
            }

            // Totales de pulsaciones por cada dispositivo (totals_by_device)
            $totalsByDevice = Keyboard::selectRaw('hardware_device_id, SUM(pulsations) as total')
                ->whereNotNull('hardware_device_id')
                ->groupBy('hardware_device_id')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) {
                    $device = \App\Models\Hardware\HardwareDevice::find($row->hardware_device_id);
                    return (object) [
                        'hardware_device_id' => $row->hardware_device_id,
                        'name' => $device?->name_friendly ?? $device?->name ?? "Device #{$row->hardware_device_id}",
                        'total' => $row->total,
                    ];
                });

            return [
                'total_global' => $totalGlobal,
                'top_year' => $topYear,
                'top_month' => $topMonth,
                'totals_by_year' => $totalsByYear,
                'top_device' => $topDevice ? (object) [
                    'hardware_device_id' => $topDevice->hardware_device_id,
                    'name' => $topDeviceName,
                    'total' => $topDevice->total,
                ] : null,
                'totals_by_device' => $totalsByDevice,
            ];
        });

        return view('keycounter.index')->with([
            'month' => $month,
            'year' => $year,
            'labelsString' => $statistics['labelsString'],
            'datasetJson' => $statistics['datasetJson'],
            'keyboard_statistics' => $statistics['keyboard_statistics'],
            'months' => $months,
            'keyboardSummary' => $keyboardSummary,
            'mouseSummary' => $mouseSummary,
            'widgets' => $widgets,
        ]);
    }

    /* Ajax */

    /**
     * Devuelve los datos en json para las pulsaciones de teclado.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKeyboardDataAjax(Request $request)
    {
        $month = (int) ($request->get('month') ?? date('m'));
        $year = (int) ($request->get('year') ?? date('Y'));

        $statistics = Keyboard::getStatisticsPreparedToGraphics($month, $year);
        $keyboard_statistics = $statistics['keyboard_statistics'];
        $labelsString = $statistics['labelsString'];
        $datasetJson = $statistics['datasetJson'];

        $data = [
            'month' => $month,
            'year' => $year,
            'labelsString' => $labelsString,
            'datasetJson' => $datasetJson,
            'keyboard_statistics' => $keyboard_statistics,
        ];

        $response = JsonHelper::success($data);

        return response()->json($response);
    }

    public function getMouseDataAjax()
    {
        // TODO
    }
}
