<?php

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function date;
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

        //dd(Keyboard::statistics());


        /* PREPARANDO ESTADÍSTICAS */

        $keyboard_statistics = Keyboard::statistics($month, $year);
        $stats = $keyboard_statistics['data']->sortBy('day');
        $colors = ['#3e95cd', '#8e5ea2', '#007bff', '#e8c3b9', '#c45850',
            '#000000', '#00ff00', '#0000ff', '#3cba9f'];

        $days = array_unique($stats->pluck('day')->toArray());
        $devices = $keyboard_statistics['devices_ids'];

        $labels = [];
        $dataset = [];
        $datasetTMP = [];

        ## Array temporal de días con el valor de las pulsaciones por día de todos los dispositivos.
        $totalTMP = [];

        ## Recorro todos los días y genero por cada dispositivo un array con los datos.
        foreach ($days as $day) {
            $labels[] = (new Carbon($day))->format('d');

            foreach ($devices as $device) {
                $s = $stats->where('day', $day)->where('device_id', $device)->first();

                ## Compruebo que haya registro para este dispositivo este día o seteo 0.
                if ($s && isset($datasetTMP[$device])) {
                    if (!isset($datasetTMP[$device]['label'])) {
                        $datasetTMP[$device]['label'] = $s->device_name;
                    }

                    if (!isset($datasetTMP[$device]['borderColor'])) {
                        $datasetTMP[$device]['borderColor'] = $colors[$s->device_id];
                    }

                    if (!isset($datasetTMP[$device]['fill'])) {
                        $datasetTMP[$device]['fill'] = 'false';
                    }

                    $datasetTMP[$device]['data'][] = $s->total_pulsations;
                } else if ($s) {
                    $datasetTMP[$device] = [
                        'data' => [$s->total_pulsations],
                        'label' => $s->device_name,
                        'borderColor' => $colors[$s->device_id],
                        'fill' => 'false'
                    ];
                } else {
                    $datasetTMP[$device]['data'][] = 0;
                }

                ## Añado al array total el valor actual.
                if ($s) {
                    $totalTMP[$day] = isset($totalTMP[$day]) ? $totalTMP[$day] +
                        $s->total_pulsations :
                        $s->total_pulsations;
                }
            }
        }

        ## Añado un nuevo elemento que agrupe todos los dispositivos con el total por día.
        $total = [];
        foreach ($totalTMP as $t) {
            $total[] = $t;
        }

        $datasetTMP[0] = [
            'data' => $total,
            'label' => 'Total',
            'borderColor' => '#ff0000',
            'fill' => 'false'
        ];

        ## Añado todos los datos por días y dispositivos al array final.
        foreach ($datasetTMP as $d) {
            $dataset[] = $d;
        }

        ## Convierto los datos a string y json para luego extraerlos en javascript.
        $labelsString = implode(',', array_unique($labels));
        $datasetJson = json_encode($dataset);

        return view('keycounter.index')->with([
            'month' => $month,
            'year' => $year,
            'labelsString' => $labelsString,
            'datasetJson' => $datasetJson,
            'keyboard_statistics' => $keyboard_statistics,
            'meses' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo',
                        'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre',
                        'Noviembre', 'Diciembre'],
            'keyboard' => Keyboard::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('pulsations', '>=', 1)
                ->orderBy('created_at', 'DESC')
                ->paginate(100),
            'mouse' => Mouse::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('total_clicks', '>', 0)
                ->where('clicks_average', '>', 0)
                ->orderBy('created_at', 'DESC')
                ->paginate(100),
        ]);
    }
}
