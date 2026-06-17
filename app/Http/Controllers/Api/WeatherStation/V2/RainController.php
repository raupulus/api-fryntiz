<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreRainRequest;
use App\Http\Resources\V2\WeatherStation\RainResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de lluvia para API V2.
 */
class RainController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de lluvia con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'rain',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(RainResource::collection($data));
    }

    /**
     * Almacena dato de lluvia.
     */
    public function store(StoreRainRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['rain' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
