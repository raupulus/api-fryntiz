<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para datos de presión en API V2.
 */
class PressureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'value' => $this->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
