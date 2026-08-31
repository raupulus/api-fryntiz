<?php

declare(strict_types=1);

namespace App\Services\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio encargado de procesar y almacenar la telemetría y detección de vuelos (ADSB).
 */
class AirFlightService
{
    /**
     * Registra un sondeo del receptor ADS-B.
     *
     * El sondeo trae dos cosas distintas y van a dos tablas distintas:
     *
     * - **el avión** (`icao`, y con el tiempo `country`, `category`, `flag`) →
     *   `airflight_airplanes`, una fila por aparato;
     * - **la posición** (`lat`, `lon`, `altitude`, `speed`, `track`, `squawk`,
     *   `flight`, `messages`) → `airflight_routes`, una fila por sondeo.
     *
     * Antes esto era un único `AirFlightAirPlane::create($data)`. De los 11
     * campos validados, el `$fillable` sólo dejaba pasar `icao`; los otros diez
     * ni siquiera son columnas de esa tabla. **El receptor mandaba posiciones y
     * la API guardaba el hexadecimal** (**N291**). Y como `AirFlightResource`
     * lee la posición de `latestRoute`, que nunca se creaba, el mapa recibía
     * lat/lon/altitud nulos y `rssi` fijo en -100.
     *
     * Además el avión se busca por `icao` en vez de crearse a ciegas: el
     * receptor reporta el mismo aparato cada pocos segundos mientras esté a la
     * vista, y `create()` a pelo generaba cientos de filas idénticas
     * (**N292**).
     *
     * @param  array<string,mixed>  $data  Sondeo ya validado.
     * @param  int|null  $userId  Receptor que lo vio (**N293**).
     */
    public function addAircraft(array $data, ?int $userId = null, ?int $hardwareDeviceId = null): AirFlightAirPlane
    {
        $icao = isset($data['icao']) ? trim((string) $data['icao']) : '';

        $aircraft = AirFlightAirPlane::query()->firstOrNew(
            $icao !== '' ? ['icao' => $icao] : ['icao' => null]
        );

        if (! $aircraft->exists) {
            $aircraft->user_id = $userId;
            $aircraft->hardware_device_id = $hardwareDeviceId;
            $aircraft->seen_first_at = now();
        }

        $aircraft->seen_last_at = now();
        $aircraft->save();

        $path = $this->routeFieldsOnly($data);

        if ($path !== []) {
            $aircraft->routes()->create($path + [
                'user_id' => $userId,
                'hardware_device_id' => $hardwareDeviceId,
                'seen_at' => now(),
            ]);

            $aircraft->setRelation('latestRoute', $aircraft->routes()->orderByDesc('seen_at')->first());
        }

        return $aircraft;
    }

    /**
     * Procesa un lote de sondeos. Hasta 500 por petición: es como el receptor
     * ahorra radio.
     *
     * @param  array<int,array<string,mixed>>  $records
     * @return array<int,AirFlightAirPlane>
     */
    public function addAircraftBatch(array $records, ?int $userId = null, ?int $hardwareDeviceId = null): array
    {
        $stored = [];

        foreach ($records as $record) {
            $stored[] = $this->addAircraft($record, $userId, $hardwareDeviceId);
        }

        return $stored;
    }

    /**
     * Separa del sondeo lo que es posición, con los nombres de las columnas de
     * `airflight_routes`.
     *
     * `seen` y `seen_pos` son "hace cuántos segundos", no una marca de tiempo:
     * el esquema guarda `seen_at`, que se pone al recibir. Por eso no se copian.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function routeFieldsOnly(array $data): array
    {
        $path = [];

        foreach (['squawk', 'flight', 'lat', 'lon', 'altitude', 'vert_rate', 'track', 'speed', 'messages', 'rssi', 'emergency'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $path[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        return $path;
    }

    /**
     * Obtiene el historial paginado de aviones detectados junto con sus rutas asociadas.
     *
     * @param  int  $perPage  Cantidad de registros por página (por defecto 50).
     * @return LengthAwarePaginator Paginador con el historial de vuelos.
     */
    public function getAircraftHistory(int $perPage = 50): LengthAwarePaginator
    {
        return AirFlightAirPlane::with('latestRoute')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Aviones vistos en los últimos minutos, con su última posición conocida.
     * Es la fuente de datos que consume el mapa en vivo.
     *
     * @param  int  $minutes  Ventana de actividad reciente (por defecto 10 minutos).
     */
    public function getActiveAircrafts(int $minutes = 10): Collection
    {
        return AirFlightAirPlane::with(['latestRoute', 'trail'])
            ->where('seen_last_at', '>=', now()->subMinutes($minutes))
            ->orderByDesc('seen_last_at')
            ->get();
    }
}
