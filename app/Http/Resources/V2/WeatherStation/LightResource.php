<?php

namespace App\Http\Resources\V2\WeatherStation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'lumens' => $this->lumens,
            'index' => $this->index,
            'lux' => $this->lux,
            'uva' => $this->uva,
            'uvb' => $this->uvb,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
