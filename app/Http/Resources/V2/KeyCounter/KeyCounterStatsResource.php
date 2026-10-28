<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\KeyCounter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para estadísticas acumuladas de KeyCounter de un usuario.
 *
 * @property-read array{
 *     user_id: int,
 *     lifetime_pulsations: int,
 *     today_pulsations: int,
 *     last_hour_pulsations: int,
 *     lifetime_clicks: int,
 *     today_clicks: int,
 *     last_hour_clicks: int,
 *     today_from: string,
 *     last_hour_from: string,
 *     generated_at: string
 * } $resource
 */
class KeyCounterStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource['user_id'],
            'lifetime_pulsations' => $this->resource['lifetime_pulsations'],
            'today_pulsations' => $this->resource['today_pulsations'],
            'last_hour_pulsations' => $this->resource['last_hour_pulsations'],
            'lifetime_clicks' => $this->resource['lifetime_clicks'],
            'today_clicks' => $this->resource['today_clicks'],
            'last_hour_clicks' => $this->resource['last_hour_clicks'],
            'today_from' => $this->resource['today_from'],
            'last_hour_from' => $this->resource['last_hour_from'],
            'generated_at' => $this->resource['generated_at'],
        ];
    }
}
