<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\AirFlight\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\AirFlight\V2\StoreAirFlightRequest;
use App\Http\Requests\Api\AirFlight\V2\StoreBatchAirFlightRequest;
use App\Http\Resources\V2\AirFlight\AirFlightResource;
use App\Models\AirFlight\AirFlightAirPlane;
use App\Services\AirFlight\AirFlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AirFlight.
 *
 * `GET /airflight/db/{bkey}` se ha retirado: devolvía siempre 404 porque el
 * dataset del registro OACI no se mantiene. Un endpoint que sólo sabe decir
 * «no encontrado» es peor que no tenerlo — parece que existe (cierra P-REST-1).
 *
 * `GET /airflight/history` tampoco está: el historial es la misma colección de
 * aviones acotada por fechas, no otro recurso.
 */
class AirFlightController extends BaseApiController
{
    public function __construct(private AirFlightService $service) {}

    /**
     * Información del receptor (posición, refresco, historial).
     */
    public function receiver(): JsonResponse
    {
        return $this->successResponse([
            // No se guardan snapshots temporales para reproducir el historial
            // de recorrido (solo la última posición por avión), así que se
            // desactiva la reproducción de historial en el mapa.
            'history' => 0,
            'lat' => 36.7381,
            'lon' => -6.4301,
            'refresh' => 5000,
            'version' => 'api raupulus v2',
        ]);
    }

    /**
     * Aviones detectados.
     *
     * Absorbe lo que era `GET /airflight/history`: el historial no es otro
     * recurso, es la misma colección sin la ventana de actividad reciente.
     *
     *   (sin parámetros)      los vistos en los últimos 10 minutos — el mapa en vivo
     *   ?minutes=60           ventana de actividad a medida
     *   ?from=&to=            historial por fechas, paginado
     */
    public function aircrafts(Request $request): JsonResponse
    {
        $porFechas = $request->filled('from') || $request->filled('to');

        // El mapa en vivo pide "lo que hay ahora": son pocas filas y las quiere
        // todas de golpe, sin paginar.
        if (! $porFechas) {
            $minutes = max(1, min($request->integer('minutes') ?: 10, 1440));

            return $this->successResponse(
                AirFlightResource::collection($this->service->getActiveAircrafts($minutes))->resolve()
            );
        }

        $collectionQuery = new CollectionQuery(
            filterable: ['icao', 'seen_last_at', 'created_at'],
            sortable: ['seen_last_at', 'created_at'],
            defaultSortColumn: 'seen_last_at',
        );

        $query = AirFlightAirPlane::query()->with('latestRoute');

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            AirFlightResource::class
        );
    }

    /**
     * Registra un avión detectado.
     */
    public function store(StoreAirFlightRequest $request): JsonResponse
    {
        $airplane = $this->service->addAircraft(
            $request->validated(),
            auth()->id(),
            $request->integer('hardware_device_id') ?: null
        );

        return $this->createdResponse(
            new AirFlightResource($airplane),
            'Avion registrado'
        );
    }

    /**
     * Registra un lote de aviones detectados.
     */
    public function storeBatch(StoreBatchAirFlightRequest $request): JsonResponse
    {
        $stored = $this->service->addAircraftBatch(
            $request->validated()['data'],
            auth()->id(),
            $request->integer('hardware_device_id') ?: null
        );

        return $this->createdResponse([
            'count' => count($stored),
        ], 'Lote registrado');
    }
}
