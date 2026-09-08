<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Resource para avión detectado en API V2, junto con su última posición conocida.
 *
 * @mixin AirFlightAirPlane
 */
class AirFlightResource extends JsonResource
{
    /*
     * Estas relaciones se leen directamente y NO con `whenLoaded()`: quien use
     * este resource tiene que cargarlas con su `with()`.
     *
     * Es una decisión, no un olvido (API-05). `whenLoaded()` haría DESAPARECER
     * la clave del JSON cuando la relación no viene cargada, que es un fallo
     * más silencioso que el que evita: hoy, sin eager load, salta
     * `preventLazyLoading` en local y se ve enseguida. Todos los llamantes
     * actuales cargan lo que hace falta y no hay N+1 real.
     */
    public function toArray(Request $request): array
    {
        $route = $this->latestRoute;

        // Último mensaje CON posición, que puede ser anterior a `$route`: un
        // squawk o una altitud llegan a veces sin lat/lon nuevos. Sin esta
        // distinción, un avión con posición reciente de verdad se mandaba al
        // mapa con `lat`/`lon` a `null` en cuanto le llegaba después
        // cualquier mensaje sin posición, y el frontend lo dibujaba en
        // (0, 0) en vez de en su sitio. Se lee directo, como `latestRoute`
        // y `trail`: quien use este resource tiene que cargarla con su
        // `with()` (ver nota de la clase).
        $position = $this->latestPosition;

        $seen = $route?->seen_at ? Carbon::parse($route->seen_at)->diffInSeconds(now()) : null;
        $seenPos = $position?->seen_at ? Carbon::parse($position->seen_at)->diffInSeconds(now()) : null;

        return [
            'id' => $this->id,
            'icao' => $this->icao,
            'category' => $this->category,
            'flight' => $route?->flight,
            'squawk' => $route?->squawk,
            'lat' => $position?->lat,
            'lon' => $position?->lon,
            'altitude' => $route?->altitude,
            'vert_rate' => $route?->vert_rate,
            'speed' => $route?->speed,
            'track' => $route?->track,
            'rssi' => $route?->rssi !== null ? (float) $route->rssi : -100.0,
            'seen' => $seen,
            'seen_pos' => $seenPos,
            'messages' => $route?->messages,
            // Recorrido conocido [lon, lat] (más antiguo primero), para que el
            // mapa pueda dibujar la línea de vuelo desde el primer sondeo, sin
            // esperar a acumularla en vivo.
            'trail' => $this->relationLoaded('trail')
                ? $this->trail->take(-50)->map(fn ($r) => [(float) $r->lon, (float) $r->lat])->values()
                : [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
