<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Energy;

use App\Models\Hardware\HardwareEnergyReading;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON unificado para lecturas de energía (D115, Fase 5).
 *
 * @mixin HardwareEnergyReading
 */
class EnergyReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'hardware_energy_id' => $this->hardware_energy_id,
            'role' => $this->hardwareEnergy?->role,

            'measured' => [
                'amperage' => $this->amperage,
                'voltage' => $this->voltage,
                'power' => $this->power,
                'delta_seconds' => $this->delta_seconds,
                'temperature' => $this->temperature,
                'battery_voltage' => $this->battery_voltage,
                'battery_percentage' => $this->battery_percentage,
                'fan' => $this->fan,
                'charging_status' => $this->charging_status,
                'charging_status_label' => $this->charging_status_label,
                'light_status' => $this->light_status,
                'light_brightness' => $this->light_brightness,
            ],

            'derived' => [
                'energy_wh' => $this->energy_wh,
                'energy_ah' => $this->energy_ah,
            ],

            'sources' => [
                'energy' => $this->energy_source,
                'voltage' => $this->voltage_source,
            ],

            'is_suspicious' => (bool) $this->is_suspicious,
            'suspicious_reason' => $this->suspicious_reason,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
