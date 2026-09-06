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

        // API-06: esto llamaba a getStationReadings() por estación, o sea doce
        // consultas multiplicadas por el número de estaciones de la zona. La
        // versión por lotes agrupa por sensor y el coste deja de crecer con el
        // número de estaciones.
        $readings = $this->service->getStationsReadings(
            $this->service->getZoneStations($zone, $request->query('location_type'))
        );

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

    /**
     * Lectura agregada de una zona.
     *
     * Para cada sensor devuelve el dato más reciente de **cualquier** estación
     * de la zona, en vez de atarse a un aparato concreto: si uno deja de subir,
     * los demás siguen dando la medida. Es lo que consume el widget de portada.
     *
     * El tipo de ubicación acota el resto de sensores (normalmente `outdoor`),
     * pero la presión sale de toda la zona: el barómetro suele estar dentro
     * porque mide igual y a la interperie se estropea antes.
     */
    public function zone(string $zone, ?string $locationType = null): JsonResponse
    {
        $lecturas = $this->service->getZoneReadings($zone, $locationType);

        if ($lecturas === null) {
            return $this->notFoundResponse('Zona sin estaciones meteorologicas');
        }

        return $this->successResponse(new WeatherStationResource($lecturas));
    }
}
