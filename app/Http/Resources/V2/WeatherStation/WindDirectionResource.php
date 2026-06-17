<?php

namespace App\Http\Resources\V2\WeatherStation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WindDirectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'resistance' => $this->resistance,
            'direction' => $this->direction,
            'grades' => $this->grades,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
