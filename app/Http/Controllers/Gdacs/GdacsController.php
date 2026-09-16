<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gdacs;

use App\Http\Controllers\Controller;
use App\Models\Gdacs\GdacsEvent;
use Illuminate\View\View;

/**
 * Listado público de alertas GDACS dentro del radio de Chipiona.
 *
 * Vive fuera de la API a propósito: es una página del sitio, no un recurso
 * consumido por terceros (ver criterio equivalente en
 * WeatherStationController::widget()).
 */
class GdacsController extends Controller
{
    public function index(): View
    {
        $events = GdacsEvent::query()
            ->orderByDesc('from_date')
            ->paginate(50)
            ->withQueryString();

        return view('gdacs.index', compact('events'));
    }
}
