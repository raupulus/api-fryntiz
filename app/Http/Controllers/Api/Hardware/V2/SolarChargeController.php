<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Hardware\V2\StoreSolarChargeRequest;
use App\Http\Resources\V2\Hardware\SolarChargeResource;
use App\Services\Hardware\HardwareService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de carga solar para API V2.
 */
class SolarChargeController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

    /**
     * Almacena datos de carga solar.
     */
    public function store(StoreSolarChargeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $charge = $this->service->storeSolarCharge($data);

        $this->storeDeviceInfoIfPresent($request, $this->service, (int) $data['hardware_device_id']);

        return $this->createdResponse(
            new SolarChargeResource($charge),
            'Datos de carga solar almacenados'
        );
    }
}
