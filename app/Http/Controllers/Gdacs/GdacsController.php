<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gdacs;

use App\Http\Controllers\Controller;
use App\Models\Gdacs\GdacsEvent;
use App\Support\Pagination\DeferredPaginator;
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
        $events = DeferredPaginator::paginate(
            baseQuery: GdacsEvent::query(),
            perPage: 50,
            cacheKey: 'gdacs:web:total_count',
            columns: ['*'],
            orderColumn: 'from_date',
            countTtlSeconds: 3600,
        );

        return view('gdacs.index', compact('events'));
    }
}
