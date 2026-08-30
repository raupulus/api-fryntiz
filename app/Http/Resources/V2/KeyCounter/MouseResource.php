<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\KeyCounter;

use App\Models\KeyCounter\Mouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para registro de ratón en API V2.
 *
 * Se quitó `score`: `keycounter_mouse` no tiene esa columna, así que salía
 * `null` en todas las respuestas (**R-6**, **N280**). El ratón no puntúa; el
 * que puntúa es el teclado.
 *
 * @mixin Mouse
 */
class MouseResource extends JsonResource
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
            'clicks_left' => $this->clicks_left,
            'clicks_right' => $this->clicks_right,
            'clicks_middle' => $this->clicks_middle,
            'total_clicks' => $this->total_clicks,
            'clicks_average' => $this->clicks_average,
            'weekday' => $this->weekday,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
