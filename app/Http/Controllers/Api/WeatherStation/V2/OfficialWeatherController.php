<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\WeatherStation\OfficialWeatherResource;
use App\Services\WeatherStation\OfficialWeatherService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de información meteorológica y mareas de fuentes oficiales
 * (`/api/v2/weather-station-official`).
 *
 * Sirve los datos ya sincronizados en base de datos desde AEMET OpenData,
 * Open-Meteo Marine y las estaciones físicas de referencia sin lanzar nuevas
 * peticiones a terceros por cada proyecto o portal consumidor.
 */
class OfficialWeatherController extends BaseApiController
{
    public function __construct(private readonly OfficialWeatherService $service) {}

    /**
     * Parte meteorológico y tabla de mareas del día para una ciudad/municipio.
     */
    public function city(string $city): JsonResponse
    {
        $report = $this->service->getForCity($city);

        if ($report === null) {
            return $this->notFoundResponse('Ciudad no disponible en el catálogo meteorológico oficial');
        }

        return $this->successResponse(new OfficialWeatherResource($report));
    }

    /**
     * Parte meteorológico y tabla de mareas del día para una provincia.
     */
    public function province(string $province): JsonResponse
    {
        $report = $this->service->getForProvince($province);

        if ($report === null) {
            return $this->notFoundResponse('Provincia no disponible en el catálogo meteorológico oficial');
        }

        return $this->successResponse(new OfficialWeatherResource($report));
    }
}
