<?php

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreSensorRequest;
use App\Http\Resources\V2\WeatherStation\Eco2Resource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de eCO2 para API V2.
 */
class Eco2Controller extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de eCO2 con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'eco2',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(Eco2Resource::collection($data));
    }

    /**
     * Almacena dato de eCO2.
     */
    public function store(StoreSensorRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['eco2' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
