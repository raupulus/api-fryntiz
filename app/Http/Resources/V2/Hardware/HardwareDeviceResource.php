<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Hardware;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para dispositivo hardware en API V2.
 *
 * @mixin HardwareDevice
 */
class HardwareDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'name_friendly' => $this->name_friendly,
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'description' => $this->description,
            'hardware_version' => $this->hardware_version,
            'software_version' => $this->software_version,
            'serial_number' => $this->serial_number,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
