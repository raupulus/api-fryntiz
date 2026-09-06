<?php

declare(strict_types=1);

namespace App\Http\Controllers\AirFlight;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\AirFlight\AirFlightResource;
use App\Models\AirFlight\AirFlightAirPlane;
use App\Services\AirFlight\AirFlightService;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Class AirFlightController
 */
class AirFlightController extends Controller
{
    /**
     * Aviones activos para el mapa de esta misma web.
     *
     * Vive en el bloque **web** y no en la API a propósito: lo consume el mapa
     * de `/airflight`, que es una página propia, no una integración. Devuelve
     * lo justo que pinta el mapa —los vistos hace poco— y va cacheado unos
     * segundos.
     *
     * Hasta el 2026-09-06 el mapa llamaba a `GET /api/v2/airflight/aircrafts`,
     * y por eso esa ruta tenía que estar abierta al mundo: la ability
     * `airflight:read` no protegía nada. Ahora la API pide token y ofrece a
     * cambio el historial por fechas, filtros y paginación.
     */
    public function aircrafts(Request $request): JsonResponse
    {
        $minutos = max(1, min((int) $request->query('minutes', '10'), 1440));

        // Los aviones se refrescan cada 5 s en el mapa (`refresh` de
        // `receiver()`), así que diez segundos de caché quitan casi todas las
        // consultas sin que se note en pantalla.
        $aviones = Cache::remember(
            'airflight:web:aircrafts:'.$minutos,
            10,
            fn () => AirFlightResource::collection(
                app(AirFlightService::class)->getActiveAircrafts($minutos)
            )->resolve()
        );

        return response()->json(['success' => true, 'data' => $aviones]);
    }

    /**
     * Configuración del receptor para el mapa.
     *
     * Son constantes de pintado (dónde centrar el mapa y cada cuánto refrescar),
     * no datos de nadie.
     */
    public function receiver(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                // No se guardan snapshots temporales para reproducir el
                // historial de recorrido (sólo la última posición por avión),
                // así que se desactiva la reproducción de historial.
                'history' => 0,
                'lat' => 36.7381,
                'lon' => -6.4301,
                'refresh' => 5000,
                'version' => 'api raupulus v2',
            ],
        ]);
    }

    /**
     * Lleva a la vista de resumen para visualizar la depuración.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $now = Carbon::now();
        $lastHour = (clone $now)->subHour();

        $planes = AirFlightAirPlane::with('latestRoute')
            ->where('seen_last_at', '>=', $lastHour)
            ->orderByDesc('seen_last_at')
            ->paginate(20);

        return view('airflight.index')->with([
            'planes' => $planes,
        ]);
    }
}
