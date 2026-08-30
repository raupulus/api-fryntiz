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
 * Subida de lecturas del monitor de energía (D115).
 */
class EnergyMonitorController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

    /**
     * Almacena las lecturas de un monitor de energía.
     *
     * La respuesta lleva `warnings` cuando algo es raro pero se ha guardado:
     * una corriente negativa, un elemento sin tensión, un canal sin dar de
     * alta. Sin eso, un montaje mal configurado responde 201 durante meses.
     */
    public function store(StoreEnergyRequest $request): JsonResponse
    {
        $data = $request->validated();

        ['readings' => $readings, 'warnings' => $warnings] = $this->service->storeEnergyData($data);

        $this->storeDeviceInfoIfPresent($request, $this->service, (int) $data['hardware_device_id']);

        // Si no se ha guardado nada es porque el dispositivo no tiene ningún
        // elemento activo en `hardware_energy`, o porque ninguna `pos` de la
        // petición casa con una `sensor_position`. Antes respondía 201 igual y
        // el dato se perdía sin avisar.
        if ($readings === []) {
            return $this->errorResponse(
                'Ninguna lectura se ha podido asignar: revisa que el dispositivo tenga elementos '.
                'activos dados de alta y que los canales coincidan con sus posiciones de sensor.',
                422,
                $warnings
            );
        }

        return $this->withWarnings(
            $this->createdResponse(
                EnergyMonitorResource::collection($readings),
                'Lecturas de energia almacenadas'
            ),
            $warnings
        );
    }
}
