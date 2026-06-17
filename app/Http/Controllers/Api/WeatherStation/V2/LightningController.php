<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreLightningRequest;
use App\Http\Resources\V2\WeatherStation\LightningResource;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de datos de rayos/relámpagos para API V2.
 */
class LightningController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Lista datos de rayos con filtro opcional por rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getPreparedData(
            'lightning',
            $request->query('from'),
            $request->query('to')
        );

        return $this->successResponse(LightningResource::collection($data));
    }

    /**
     * Almacena dato de rayo/relámpago.
     */
    public function store(StoreLightningRequest $request): JsonResponse
    {
        $record = $this->service->storeGenericData(
            ['lightning' => [$request->validated()]],
            $request->input('hardware_device_id')
        );

        return $this->createdResponse($record, 'Datos almacenados correctamente');
    }
}
