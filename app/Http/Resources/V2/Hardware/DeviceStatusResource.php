<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Hardware;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el último estado conocido de un dispositivo en API V2.
 *
 * @mixin HardwareDevice
 */
class DeviceStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'hardware_device_id' => $this->id,
            'temp' => $this->temp,
            'voltage' => $this->voltage,
            'battery_level' => $this->battery_level,
            'cpu' => $this->cpu,
            'disk' => $this->disk,
            'uptime' => $this->uptime,
            'ip_local' => $this->ip_local,
            'ip_public' => $this->ip_public,
            'extra' => $this->extra,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
        ];
    }
}
