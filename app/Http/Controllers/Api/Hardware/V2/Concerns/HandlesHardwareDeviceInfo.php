<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2\Concerns;

use App\Services\Hardware\HardwareService;
use Illuminate\Http\Request;

/**
 * Permite que cualquier subida IoT del módulo Hardware adjunte, de forma
 * opcional, el último estado del dispositivo agrupado en `hardware_device_info`.
 *
 * El estado se guarda como último estado conocido del dispositivo (sin
 * histórico). La pertenencia del dispositivo ya se garantiza mediante la regla
 * `OwnedHardwareDevice` del FormRequest sobre el identificador principal, por lo
 * que aquí solo se recibe el `$deviceId` ya validado.
 */
trait HandlesHardwareDeviceInfo
{
    /**
     * Si el cuerpo trae `hardware_device_info`, actualiza el estado del
     * dispositivo indicado. No hace nada si la clave no está presente.
     *
     * @param  int  $deviceId  Dispositivo ya validado como propiedad del usuario.
     */
    protected function storeDeviceInfoIfPresent(Request $request, HardwareService $service, int $deviceId): void
    {
        $info = $request->input('hardware_device_info');

        if (! is_array($info) || $info === []) {
            return;
        }

        $service->updateDeviceStatus($deviceId, $info);
    }
}
