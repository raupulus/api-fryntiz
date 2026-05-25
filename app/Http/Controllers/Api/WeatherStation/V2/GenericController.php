<?php

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreGenericRequest;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de datos genéricos de estación meteorológica para API V2.
 */
class GenericController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Almacena datos genéricos de múltiples sensores.
     */
    public function store(StoreGenericRequest $request): JsonResponse
    {
        $stored = $this->service->storeGenericData(
            $request->input('data', []),
            $request->input('hardware_device_id')
        );

        return $this->createdResponse([
            'count' => count($stored),
        ], 'Datos almacenados correctamente');
    }
}
