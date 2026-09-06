<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2\Concerns;

use App\Services\Hardware\HardwareService;
use App\Support\Http\ClientIp;
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

        // La IP pública la pone el servidor, igual que en
        // `PUT /hardware/devices/{device}/status`.
        //
        // Ese endpoint la resolvía en su propio FormRequest, y por eso el bloque
        // agrupado se quedaba fuera: las nueve rutas que aceptan
        // `hardware_device_info` —estación meteorológica, KeyCounter,
        // SmartPlant, AirFlight, energía y carga solar— guardaban el estado sin
        // IP pública ninguna. Aquí es donde se aplica el bloque, así que aquí es
        // donde tiene que resolverse, y vale para todas de una vez.
        //
        // Se sobreescribe siempre lo que mande el cliente. Si no hay ninguna
        // pública que resolver —desarrollo, o una NAT sin proxy delante— se
        // guarda null, en vez de meter una privada en una columna que dice
        // «pública».
        $info['ip_public'] = ClientIp::public($request);

        $service->updateDeviceStatus($deviceId, $info);
    }
}
