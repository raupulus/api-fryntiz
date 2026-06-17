<?php

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreWindDirectionRequest;
use App\Http\Resources\V2\WeatherStation\WindDirectionResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de dirección del viento para API V2.
 */
class WindDirectionController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de dirección del viento con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'wind_direction',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(WindDirectionResource::collection($data));
    }

    /**
     * Almacena dato de dirección del viento.
     */
    public function store(StoreWindDirectionRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['wind_direction' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
