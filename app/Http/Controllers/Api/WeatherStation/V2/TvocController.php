<?php

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreSensorRequest;
use App\Http\Resources\V2\WeatherStation\TvocResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de TVOC para API V2.
 */
class TvocController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de TVOC con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'tvoc',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(TvocResource::collection($data));
    }

    /**
     * Almacena dato de TVOC.
     */
    public function store(StoreSensorRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['tvoc' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
