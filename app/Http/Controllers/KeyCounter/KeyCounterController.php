<?php

declare(strict_types=1);

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Services\KeyCounter\KeyCounterStatisticsService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonHelper;

/**
 * Class KeyCounterController
 *
 * El controlador no calcula nada: pide a `KeyCounterStatisticsService` lo que
 * ya está cacheado. El cálculo de verdad lo paga el planificador
 * (`keycounter:warm_cache`), no quien entra en la página.
 *
 * Qué dura cuánto y por qué está en `App\Support\KeyCounter\KeyCounterCache`.
 */
class KeyCounterController extends Controller
{
    public function __construct(private KeyCounterStatisticsService $statistics) {}

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

        // Lo más caro de la página: agrega todas las rachas del mes por día y
        // por dispositivo. Un mes cerrado se guarda para siempre —no va a
        // recibir rachas nuevas—; el mes en curso y el anterior los reescribe
        // el refresco horario, así que aquí ya están hechos.
        $statistics = $this->statistics->cachedGraph($year, $month);

        $monthSummary = $this->statistics->monthSummary($statistics['keyboard_statistics']);

        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo',
            'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre',
            'Noviembre', 'Diciembre'];

        // Resúmenes y widgets: misma ventana que la gráfica del mes vivo, para
        // que las dos mitades de la página no cuenten cosas de momentos
        // distintos.
        $keyboardSummary = $this->statistics->cachedKeyboardSummary();
        $mouseSummary = $this->statistics->cachedMouseSummary();

        $totalsByYear = $this->statistics->cachedYearlyTotals((int) date('Y'));
        $topYear = $totalsByYear->sortByDesc('total')->first();

        $widgets = $this->statistics->cachedWidgets();
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
     *
     * Va por la misma caché que la vista: si algún día se enruta, no puede
     * saltarse el retardo de privacidad que respeta el resto de la página.
     */
    public function getKeyboardDataAjax(Request $request): JsonResponse
    {
        $month = (int) ($request->get('month') ?? date('m'));
        $year = (int) ($request->get('year') ?? date('Y'));

        $statistics = $this->statistics->cachedGraph($year, $month);

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
