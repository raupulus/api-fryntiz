<?php

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Carbon\Carbon;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use JsonHelper;
use function array_unique;
use function date;
use function dd;
use function implode;
use function json_encode;
use function response;
use function route;
use function view;

/**
 * Class ViewsController
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
        $month = $request->get('month') ?? date('m');
        $year = $request->get('year') ?? date('Y');

        $statistics = Keyboard::getStatisticsPreparedToGraphics($month, $year);

        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo',
                   'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre',
                   'Noviembre', 'Diciembre'];

        return view('keycounter.index')->with([
            'month' => $month,
            'year' => $year,
            'labelsString' => $statistics['labelsString'],
            'datasetJson' => $statistics['datasetJson'],
            'keyboard_statistics' => $statistics['keyboard_statistics'],
            'months' => $months,
            'keyboard' => Keyboard::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('pulsations', '>', 0)
                ->orderByDesc('created_at')
                ->paginate(100),
            'mouse' => Mouse::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('total_clicks', '>', 0)
                ->where('clicks_average', '>', 0)
                ->orderByDesc('created_at')
                ->paginate(100),
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
        $month = $request->get('month') ?? date('m');
        $year = $request->get('year') ?? date('Y');

        $statistics = Keyboard::getStatisticsPreparedToGraphics($month, $year);
        $keyboard_statistics = $statistics['keyboard_statistics'];
        $labelsString = $statistics['labelsString'];
        $datasetJson = $statistics['datasetJson'];

        // TODO → Preparar mejor respuesta

        $data = [
            'month' => $month,
            'year' => $year,
            'labelsString' => $labelsString,
            'datasetJson' => $datasetJson,
            'keyboard_statistics' => $keyboard_statistics,
        ];

        $response = JsonHelper::prepareSuccess($data);

        return response()->json($response);
    }

    public function getMouseDataAjax()
    {
        // TODO
    }
}
