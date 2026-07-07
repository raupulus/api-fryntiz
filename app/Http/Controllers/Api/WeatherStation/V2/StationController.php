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
 * Endpoints de lectura de estaciones meteorológicas (API V2).
 *
 * Devuelven los datos ya formateados y listos para usar (valores numéricos, sin
 * unidades). Admiten seleccionar sensores concretos con `?sensors=wind,temperature`.
 */
class StationController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Devuelve una estación por id. Si no se indica id, se usa la estación
     * principal por defecto (la primera de exterior).
     */
    public function show(ShowStationRequest $request, ?int $station = null): JsonResponse
    {
        $device = $this->service->resolveStation($station);

        if (! $device) {
            return $this->notFoundResponse('Estacion meteorologica no encontrada');
        }

        $readings = $this->service->getStationReadings($device);

        return $this->successResponse(
            new WeatherStationResource($readings),
            'Estacion meteorologica obtenida correctamente'
        );
    }

    /**
     * Devuelve todas las estaciones de una zona (colección), opcionalmente
     * acotadas por `location_type` (indoor/outdoor). Siempre devuelve una
     * colección, aunque solo haya una estación o ninguna.
     */
    public function zone(ShowZoneRequest $request, string $zone): JsonResponse
    {
        $locationType = $request->query('location_type');

        $readings = $this->service
            ->getZoneStations($zone, $locationType)
            ->map(fn ($device) => $this->service->getStationReadings($device));

        return $this->successResponse(
            WeatherStationResource::collection($readings),
            'Estaciones de la zona obtenidas correctamente'
        );
    }
}
