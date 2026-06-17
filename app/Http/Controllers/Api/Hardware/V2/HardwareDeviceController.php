<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\Hardware\HardwareDeviceResource;
use App\Services\Hardware\HardwareService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de dispositivos hardware para API V2.
 */
class HardwareDeviceController extends BaseApiController
{
    public function __construct(private HardwareService $service) {}

    /**
     * Muestra información de un dispositivo hardware.
     */
    public function show(int $id): JsonResponse
    {
        $device = $this->service->getDeviceInfo($id);

        if (! $device) {
            return $this->notFoundResponse('Dispositivo no encontrado');
        }

        return $this->successResponse(new HardwareDeviceResource($device));
    }

    /**
     * Lista los computadores del usuario autenticado.
     */
    public function computers(): JsonResponse
    {
        $computers = $this->service->getComputersList(auth()->id());

        return $this->successResponse($computers);
    }
}
