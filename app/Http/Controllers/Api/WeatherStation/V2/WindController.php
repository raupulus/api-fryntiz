<?php

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreWindRequest;
use App\Http\Resources\V2\WeatherStation\WindResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de viento para API V2.
 */
class WindController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de viento con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'wind',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(WindResource::collection($data));
    }

    /**
     * Almacena dato de viento.
     */
    public function store(StoreWindRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['wind' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
