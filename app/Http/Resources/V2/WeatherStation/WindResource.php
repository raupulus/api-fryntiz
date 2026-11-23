<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use App\Models\WeatherStation\Wind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wind
 */
class WindResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'speed' => $this->speed !== null ? (float) $this->speed : null,
            'average' => $this->average !== null ? (float) $this->average : null,
            'min' => $this->min !== null ? (float) $this->min : null,
            'max' => $this->max !== null ? (float) $this->max : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
