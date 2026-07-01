<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use App\Models\WeatherStation\Lightning;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lightning
 */
class LightningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'distance' => $this->distance,
            'energy' => $this->energy,
            'noise_floor' => $this->noise_floor,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
