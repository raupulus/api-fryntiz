<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreAirQualityRequest;
use App\Http\Resources\V2\WeatherStation\AirQualityResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de calidad del aire para API V2.
 */
class AirQualityController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de calidad del aire con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'air_quality',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(AirQualityResource::collection($data));
    }

    /**
     * Almacena dato de calidad del aire.
     */
    public function store(StoreAirQualityRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['air_quality' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
