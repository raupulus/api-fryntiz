<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Hardware\V2\StoreEnergyRequest;
use App\Http\Resources\V2\Hardware\EnergyMonitorResource;
use App\Services\Hardware\HardwareService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de monitorización de energía para API V2.
 */
class EnergyMonitorController extends BaseApiController
{
    public function __construct(private HardwareService $service) {}

    /**
     * Almacena datos de monitorización de energía.
     */
    public function store(StoreEnergyRequest $request): JsonResponse
    {
        $energy = $this->service->storeEnergyData($request->validated());

        return $this->createdResponse(
            new EnergyMonitorResource($energy),
            'Datos de energia almacenados'
        );
    }
}
