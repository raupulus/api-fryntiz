<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Hardware;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para datos de carga solar en API V2.
 */
class SolarChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'date' => $this->date,
            'read_at' => $this->read_at,
            'battery_voltage' => $this->battery_voltage,
            'battery_percentage' => $this->battery_percentage,
            'temperature' => $this->temperature,
            'load_voltage' => $this->load_voltage,
            'load_amperage' => $this->load_amperage,
            'load_power' => $this->load_power,
            'energy_voltage' => $this->energy_voltage,
            'energy_amperage' => $this->energy_amperage,
            'energy_power' => $this->energy_power,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
