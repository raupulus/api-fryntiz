<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2\Concerns;

use App\Services\Hardware\HardwareService;
use Illuminate\Http\Request;

/**
 * Permite que cualquier subida IoT procese telemetría energética adjuntada
 * en el bloque `energy`.
 */
trait HandlesEnergyTelemetry
{
    /**
     * Si la petición trae el bloque `energy`, procesa e ingesta la telemetría
     * de generación, batería y consumos mediante `HardwareService::storeEnergyTelemetry()`.
     *
     * @param  int  $deviceId  Dispositivo ya validado como propiedad del usuario.
     * @return array<string, mixed>|null Resultado de la persistencia o null si no venía energía.
     */
    protected function storeEnergyTelemetryIfPresent(Request $request, HardwareService $service, int $deviceId): ?array
    {
        $energy = $request->input('energy');

        if (! is_array($energy) || $energy === []) {
            return null;
        }

        return $service->storeEnergyTelemetry($deviceId, $energy);
    }
}
