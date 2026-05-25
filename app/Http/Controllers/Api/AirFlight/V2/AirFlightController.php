<?php

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
     * Lista aviones detectados recientemente.
     */
    public function aircrafts(): JsonResponse
    {
        $aircrafts = $this->service->getAircraftHistory();

        return $this->successResponse(
            AirFlightResource::collection($aircrafts)
        );
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
