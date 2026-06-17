<?php

namespace App\Http\Resources\V2\WeatherStation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WindResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'speed' => $this->speed,
            'average' => $this->average,
            'min' => $this->min,
            'max' => $this->max,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
