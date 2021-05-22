<?php

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
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
        return view('keycounter.index')->with([
            'month' => $month,
            'year' => $year,
            'keyboard_statistics' => Keyboard::statistics($month, $year),
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
