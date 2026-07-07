<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Hardware;

use App\Models\Hardware\HardwareEnergy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para datos de energía en API V2.
 *
 * @mixin HardwareEnergy
 */
class EnergyMonitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'cpu_avg' => $this->cpu_avg,
            'intensity' => $this->intensity,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
