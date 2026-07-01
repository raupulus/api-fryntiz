<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use App\Models\WeatherStation\Temperature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Temperature
 */
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
