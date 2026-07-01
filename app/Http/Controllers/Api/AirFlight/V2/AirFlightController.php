<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\AirFlight\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\AirFlight\V2\StoreAirFlightRequest;
use App\Http\Requests\Api\AirFlight\V2\StoreBatchAirFlightRequest;
use App\Http\Resources\V2\AirFlight\AirFlightResource;
use App\Services\AirFlight\AirFlightService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de vuelos detectados para API V2.
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
     * Lista aviones vistos en los últimos minutos, con su última posición.
     */
    public function aircrafts(): JsonResponse
    {
        return $this->successResponse(
            AirFlightResource::collection($this->service->getActiveAircrafts())
        );
    }

    /**
     * Base de datos de matrícula/tipo de avión por prefijo ICAO.
     *
     * No se mantiene ese dataset (requeriría importar el registro OACI
     * completo), así que se responde "no encontrado" de forma consistente.
     */
    public function db(string $bkey): JsonResponse
    {
        return $this->notFoundResponse('Sin datos de matrícula/tipo para este prefijo ICAO');
    }

    /**
     * Historial extendido de aviones (últimos 100).
     */
    public function history(): JsonResponse
    {
        $history = $this->service->getAircraftHistory(100);

        return $this->successResponse(
            AirFlightResource::collection($history)
        );
    }

    /**
     * Registra un avión detectado.
     */
    public function store(StoreAirFlightRequest $request): JsonResponse
    {
        $airplane = $this->service->addAircraft($request->validated());

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
        $stored = $this->service->addAircraftBatch($request->validated()['data']);

        return $this->createdResponse([
            'count' => count($stored),
        ], 'Lote registrado');
    }
}
