<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Hardware\V2\StoreDeviceStatusRequest;
use App\Http\Resources\V2\Hardware\DeviceStatusResource;
use App\Services\Hardware\HardwareService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador para el estado de dispositivos de hardware en API V2.
 *
 * Permite subir únicamente el último estado conocido de un dispositivo (NAS,
 * Raspberry Pi, portátil, etc.): temperatura, tensión, batería, CPU, disco,
 * uptime, IPs y métricas extra. No guarda histórico, solo el último estado.
 */
class DeviceStatusController extends BaseApiController
{
    public function __construct(private HardwareService $service) {}

    /**
     * Actualiza el último estado conocido del dispositivo indicado.
     */
    public function store(StoreDeviceStatusRequest $request): JsonResponse
    {
        $data = $request->validated();

        $device = $this->service->updateDeviceStatus(
            (int) $data['hardware_device_id'],
            $data
        );

        return $this->successResponse(
            new DeviceStatusResource($device),
            'Estado del dispositivo actualizado'
        );
    }
}
