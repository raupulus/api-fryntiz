<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreLightRequest;
use App\Http\Resources\V2\WeatherStation\LightResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de luz para API V2.
 */
class LightController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de luz con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'light',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(LightResource::collection($data));
    }

    /**
     * Almacena dato de luz.
     */
    public function store(StoreLightRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['light' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
