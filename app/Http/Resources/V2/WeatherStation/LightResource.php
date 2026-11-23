<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use App\Models\WeatherStation\Light;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Light
 */
class LightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'lumens' => $this->lumens !== null ? (float) $this->lumens : null,
            'index' => $this->index !== null ? (float) $this->index : null,
            'lux' => $this->lux !== null ? (float) $this->lux : null,
            'uva' => $this->uva !== null ? (float) $this->uva : null,
            'uvb' => $this->uvb !== null ? (float) $this->uvb : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
