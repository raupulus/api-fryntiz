<?php

namespace App\Http\Resources\V2\WeatherStation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemperatureResource extends JsonResource
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
