<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\ShowStationRequest;
use App\Http\Requests\Api\WeatherStation\V2\ShowZoneRequest;
use App\Http\Resources\V2\WeatherStation\WeatherStationResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;

/**
 * Estaciones meteorológicas.
 *
 * Devuelve los datos ya formateados y listos para pintar. Admite acotar los
 * sensores con `?sensors=wind,temperature`.
 *
 * `GET /weatherstation/zone/{zone}` era una ruta propia; ahora la zona es un
 * filtro de la colección: `GET /weather-stations?zone=chipiona`.
 */
class WeatherStationController extends BaseApiController
{
    public function __construct(private readonly WeatherStationService $service) {}

    /**
     * Estaciones, opcionalmente de una zona.
     *
     *   ?zone=chipiona
     *   ?location_type=indoor|outdoor
     */
    public function index(ShowZoneRequest $request): JsonResponse
    {
        $zone = $request->query('zone');

        if (! is_string($zone) || $zone === '') {
            // Sin zona se devuelve la principal, que es lo que pedía casi todo
            // el mundo con la ruta antigua sin id.
            $device = $this->service->resolveStation();

            if (! $device) {
                return $this->successResponse([]);
            }

            return $this->successResponse([
                (new WeatherStationResource($this->service->getStationReadings($device)))->resolve(),
            ]);
        }

        $readings = $this->service
            ->getZoneStations($zone, $request->query('location_type'))
            ->map(fn ($device) => $this->service->getStationReadings($device));

        return $this->successResponse(
            WeatherStationResource::collection($readings)->resolve()
        );
    }

    /**
     * Una estación por id. Sin id, la principal (la primera de exterior).
     */
    public function show(ShowStationRequest $request, ?int $station = null): JsonResponse
    {
        $device = $this->service->resolveStation($station);

        if (! $device) {
            return $this->notFoundResponse('Estacion meteorologica no encontrada');
        }

        return $this->successResponse(
            new WeatherStationResource($this->service->getStationReadings($device))
        );
    }
}
