<?php

namespace App\Http\Resources\V2\Hardware;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para dispositivo hardware en API V2.
 */
class HardwareDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'hardware' => $this->hardware,
            'version' => $this->version,
            'serial_number' => $this->serial_number,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
