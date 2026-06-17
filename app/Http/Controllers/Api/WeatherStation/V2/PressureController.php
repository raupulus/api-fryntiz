<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreSensorRequest;
use App\Http\Resources\V2\WeatherStation\PressureResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de presión para API V2.
 */
class PressureController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de presión con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData('pressure', $request->query('from'), $request->query('to'));

        return $this->successResponse(PressureResource::collection($data));
    }

    /**
     * Almacena dato de presión.
     */
    public function store(StoreSensorRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['pressure' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
