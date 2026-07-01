<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use App\Models\WeatherStation\Rain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Rain
 */
class RainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'rain' => $this->rain,
            'rain_intensity' => $this->rain_intensity,
            'rain_month' => $this->rain_month,
            'moisture' => $this->moisture,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
