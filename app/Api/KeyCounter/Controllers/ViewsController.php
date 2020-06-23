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
            'keyboard' => Keyboard::paginate(50),
            'mouse' => Mouse::paginate(50),
        ]);
    }
}
