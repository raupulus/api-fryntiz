<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
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
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

    /**
     * Almacena datos de monitorización de energía.
     */
    public function store(StoreEnergyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $energy = $this->service->storeEnergyData($data);

        $this->storeDeviceInfoIfPresent($request, $this->service, (int) $data['hardware_device_id']);

        return $this->createdResponse(
            new EnergyMonitorResource($energy),
            'Datos de energia almacenados'
        );
    }
}
