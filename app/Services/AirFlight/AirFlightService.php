<?php

declare(strict_types=1);

namespace App\Services\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

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
            $newRoute = $this->mergeOrCreateRoute($aircraft, $path, $userId, $hardwareDeviceId);

            $aircraft->setRelation('latestRoute', $newRoute);

            // Este mensaje concreto puede no traer posición (un squawk o una
            // altitud sueltos): en ese caso `latestPosition` se deja para
            // que `AirFlightResource` la resuelva bajo demanda si hace falta,
            // en vez de asumir que la posición nueva es esta.
            if ($newRoute->lat !== null && $newRoute->lon !== null) {
                $aircraft->setRelation('latestPosition', $newRoute);
            }
        }

        return $aircraft;
    }

    /**
     * Guarda un sondeo en `airflight_routes`, fusionando en vez de duplicar
     * cuando es la misma detección re-decodificada.
     *
     * El SDR sube el contador `messages` cada vez que decodifica un mensaje
     * Mode S nuevo de ese avión. Si dos sondeos del mismo avión traen el
     * mismo `messages` dentro de la última hora, no ha llegado ningún
     * mensaje nuevo entre uno y otro: es la misma detección, que puede venir
     * con campos distintos ya decodificados (Mode S manda posición,
     * identificación y altitud en mensajes separados). Sin esto, cada campo
     * que se iba decodificando por separado generaba su propia fila con casi
     * todo a null, y la tabla "Aviones detectados" sólo veía la última —casi
     * siempre vacía— en vez de la suma de lo conocido.
     *
     * `$path` ya viene sin nulls (`routeFieldsOnly()`), así que rellenar con
     * él nunca borra un valor existente con uno vacío: sólo añade o
     * actualiza los campos que sí traen dato.
     */
    private function mergeOrCreateRoute(AirFlightAirPlane $aircraft, array $path, ?int $userId, ?int $hardwareDeviceId): AirFlightRoute
    {
        if (isset($path['messages'])) {
            $existing = $aircraft->routes()
                ->where('messages', $path['messages'])
                ->where('seen_at', '>=', now()->subHour())
                ->latest('seen_at')
                ->first();

            if ($existing !== null) {
                $existing->fill($path);
                $existing->save();

                return $existing;
            }
        }

        return $aircraft->routes()->create($path + [
            'user_id' => $userId,
            'hardware_device_id' => $hardwareDeviceId,
            'seen_at' => now(),
        ]);
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
     * Aviones con una posición real dentro de los últimos minutos. Es la
     * fuente de datos que consume el mapa en vivo.
     *
     * Filtra por `routes.seen_at` con lat/lon, no por `seen_last_at` del
     * avión: ese campo se actualiza con cualquier mensaje (p.ej. un squawk
     * sin posición), así que un avión sin ruta reciente —o sin ruta
     * alguna— seguía "visto" y se colaba en el mapa sin coordenadas que
     * pintar, apareciendo detenido en un punto inventado.
     *
     * Carga `latestPosition` además de `latestRoute`: si el mensaje más
     * reciente de un avión no trae posición, `latestRoute` no tiene lat/lon
     * aunque exista una posición reciente. `AirFlightResource` necesita las
     * dos.
     *
     * @param  int  $minutes  Ventana de actividad reciente (por defecto 10 minutos).
     */
    public function getActiveAircrafts(int $minutes = 10): Collection
    {
        return AirFlightAirPlane::with(['latestRoute', 'latestPosition', 'trail'])
            ->whereHas('routes', function ($query) use ($minutes) {
                $query->where('seen_at', '>=', now()->subMinutes($minutes))
                    ->whereNotNull('lat')
                    ->whereNotNull('lon');
            })
            ->orderByDesc('seen_last_at')
            ->get();
    }

    /**
     * Cada avión visto en la ventana, con el último valor CONOCIDO (no nulo)
     * de cada campo entre todas sus rutas de la ventana — no una fila
     * suelta. Mode S manda cada dato en mensajes separados (posición,
     * identificación, altitud...), así que "la última ruta" de un avión casi
     * siempre trae uno o dos campos y el resto a null; el resto de datos
     * están en rutas anteriores de la misma ventana, no perdidos.
     *
     * `(array_agg(col ORDER BY seen_at DESC) FILTER (WHERE col IS NOT
     * NULL))[1]` es el idiom de PostgreSQL para "último valor no nulo por
     * grupo": agrega la columna en orden descendente de fecha, descarta los
     * nulls antes de agregar, y coge el primer elemento del array resultante
     * —el más reciente de los que sí tienen dato—.
     *
     * Devuelve un `Illuminate\Database\Query\Builder` (no Eloquent) para que
     * el llamante decida `->paginate()` o `->limit()->get()` según haga
     * falta.
     */
    public function getDetectedQuery(Carbon $since): Builder
    {
        return DB::table('airflight_airplanes as p')
            ->join('airflight_routes as r', function ($join) use ($since) {
                $join->on('r.airplane_id', '=', 'p.id')
                    ->where('r.seen_at', '>=', $since);
            })
            ->groupBy('p.id', 'p.icao', 'p.seen_last_at')
            ->orderByDesc('p.seen_last_at')
            ->select([
                'p.id',
                'p.icao',
                'p.seen_last_at',
                DB::raw('(array_agg(r.flight ORDER BY r.seen_at DESC) FILTER (WHERE r.flight IS NOT NULL))[1] as flight'),
                DB::raw('(array_agg(r.squawk ORDER BY r.seen_at DESC) FILTER (WHERE r.squawk IS NOT NULL))[1] as squawk'),
                DB::raw('(array_agg(r.altitude ORDER BY r.seen_at DESC) FILTER (WHERE r.altitude IS NOT NULL))[1] as altitude'),
                DB::raw('(array_agg(r.speed ORDER BY r.seen_at DESC) FILTER (WHERE r.speed IS NOT NULL))[1] as speed'),
                DB::raw('(array_agg(r.track ORDER BY r.seen_at DESC) FILTER (WHERE r.track IS NOT NULL))[1] as track'),
                DB::raw('(array_agg(r.lat ORDER BY r.seen_at DESC) FILTER (WHERE r.lat IS NOT NULL))[1] as lat'),
                DB::raw('(array_agg(r.lon ORDER BY r.seen_at DESC) FILTER (WHERE r.lon IS NOT NULL))[1] as lon'),
            ]);
    }
}
