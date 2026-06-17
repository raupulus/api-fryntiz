<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\SmartPlant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para registro de planta inteligente en API V2.
 */
class SmartPlantRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'plant_id' => $this->plant_id,
            'hardware_device_id' => $this->hardware_device_id,
            'uv' => $this->uv,
            'pressure' => $this->pressure,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'soil_humidity' => $this->soil_humidity,
            'soil_humidity_raw' => $this->soil_humidity_raw,
            'full_water_tank' => (bool) $this->full_water_tank,
            'waterpump_enabled' => (bool) $this->waterpump_enabled,
            'vaporizer_enabled' => (bool) $this->vaporizer_enabled,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
