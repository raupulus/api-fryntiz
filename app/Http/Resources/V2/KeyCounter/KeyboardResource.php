<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\KeyCounter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para registro de teclado en API V2.
 */
class KeyboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'user_id' => $this->user_id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'duration' => $this->duration,
            'pulsations' => $this->pulsations,
            'pulsations_special_keys' => $this->pulsations_special_keys,
            'pulsation_average' => $this->pulsation_average,
            'score' => $this->score,
            'weekday' => $this->weekday,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
