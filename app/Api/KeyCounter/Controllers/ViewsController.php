<?php

namespace App\Http\Controllers\Keycounter;

use App\Http\Controllers\Controller;
use App\Keycounter\Keyboard;
use App\Keycounter\Mouse;
use function view;

class ViewsController extends Controller
{
    /**
     * Vista con las estadísticas generales para el contador de pulsaciones
     * y clicks a modo de ejemplo o muestra.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('keycounter.index')->with([
            'keyboard' => Keyboard::whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('pulsations', '>=', 1)
                ->orderBy('created_at', 'DESC')
                ->paginate(100),
            'mouse' => Mouse::whereNotNull('pulsations')
                ->orderBy('created_at', 'DESC')
                ->paginate(100),
        ]);
    }
}
