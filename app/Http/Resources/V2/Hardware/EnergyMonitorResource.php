<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Hardware;

use App\Models\Hardware\HardwarePowerLoad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una lectura de energía, ya asignada a su elemento (D115).
 *
 * Antes envolvía una fila de `hardware_energy` —la tabla de **configuración**—
 * y devolvía `cpu_avg` e `intensity`, que no son columnas suyas: salían `null`
 * en todas las respuestas (**R-3**, **N280**).
 *
 * La respuesta separa a propósito **lo medido** de **lo calculado**, y dice de
 * dónde salió cada número: un vatio-hora que da el aparato y uno derivado no
 * valen lo mismo, y mezclarlos sin saberlo estropea las sumas.
 *
 * Envuelve indistintamente lecturas de consumo y de generación: las dos
 * tablas comparten columnas porque salen del mismo trait `IsEnergyReading`.
 * El `@mixin` apunta a la de consumo como representante de las dos.
 *
 * @mixin HardwarePowerLoad
 */
class EnergyMonitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // El dispositivo **monitorizado**, no el monitor: cada canal mide un
            // aparato distinto.
            'hardware_device_id' => $this->hardware_device_id,
            'hardware_energy_id' => $this->hardware_energy_id,

            // Lo medido.
            'measured' => [
                'amperage' => $this->amperage,
                'voltage' => $this->voltage,
                'delta_seconds' => $this->delta_seconds,
                'temperature' => $this->temperature,
            ],

            // Lo calculado a partir de lo medido.
            'derived' => [
                'power' => $this->power,
                'energy_wh' => $this->energy_wh,
                'energy_ah' => $this->energy_ah,
            ],

            // De dónde salió cada número.
            'sources' => [
                'energy' => $this->energy_source,
                'voltage' => $this->voltage_source,
            ],

            // Una lectura rara se marca, nunca se descarta (D72).
            'is_suspicious' => (bool) $this->is_suspicious,
            'suspicious_reason' => $this->suspicious_reason,

            'battery_voltage' => $this->battery_voltage,
            'battery_percentage' => $this->battery_percentage,

            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
