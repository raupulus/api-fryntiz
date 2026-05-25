<?php

namespace App\Http\Resources\V2\AirFlight;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para registro de vuelo en API V2.
 */
class AirFlightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'icao' => $this->icao,
            'flight' => $this->flight,
            'squawk' => $this->squawk,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'altitude' => $this->altitude,
            'speed' => $this->speed,
            'track' => $this->track,
            'seen' => $this->seen,
            'seen_pos' => $this->seen_pos,
            'messages' => $this->messages,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
