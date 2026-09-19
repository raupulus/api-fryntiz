<?php

declare(strict_types=1);

namespace App\Services\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
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
     * - **el avión** (`icao`, `registration`, `aircraft_type`, `category`,
     *   `wtc`, `aircraft_desc`, `route_last_at`, `country`, `flag`) →
     *   `airflight_airplanes`, una fila por aparato;
     * - **la posición** (`lat`, `lon`, `altitude`, `speed`, `track`, `squawk`,
     *   `flight`, `messages`, `nic`, `rc`) → `airflight_routes`, una fila por
     *   sondeo.
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

        // Matrícula, tipo y categoría: datos fijos del aparato (no de la
        // posición), que pueden llegar en cualquier sondeo — a diferencia de
        // `user_id`/`hardware_device_id`, no son "quién lo vio la primera
        // vez", así que se aplican tanto en alta como en avión ya existente.
        // Solo si vienen con valor: igual que `routeFieldsOnly()`, nunca se
        // borra un dato ya guardado con uno vacío.
        foreach (['registration', 'aircraft_type', 'category', 'wtc', 'aircraft_desc'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && trim((string) $data[$field]) !== '') {
                $aircraft->{$field} = trim((string) $data[$field]);
            }
        }

        // País y bandera: a diferencia de lo anterior, no dependen de nada
        // que mande el receptor — se calculan del propio ICAO (rango OACI),
        // igual que hacía `airflight:fix` a mano. Se resuelven aquí para no
        // depender de ese comando: nada los mantenía al día desde que se
        // dejó de ejecutar (último avión con `country` en producción: el
        // 2026-09-02).
        if ($icao !== '' && ($aircraft->country === null || $aircraft->flag === null)) {
            $hex = AirFlightAirPlane::searchHex($icao);

            if ($hex) {
                $aircraft->country ??= $hex['country'];
                $aircraft->flag ??= $hex['flag_image'];
            }
        }

        $aircraft->seen_last_at = now();

        $isNew = ! $aircraft->exists;

        try {
            $aircraft->save();
        } catch (UniqueConstraintViolationException $e) {
            // `icao` es único: otra petición (un reintento del receptor)
            // dio de alta este mismo avión entre el `firstOrNew()` y el
            // `save()`. Se repite entera para que ahora lo encuentre y
            // aplique estos datos sobre la fila que ya existe.
            if (! $isNew) {
                throw $e;
            }

            return $this->addAircraft($data, $userId, $hardwareDeviceId);
        }

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

                // `route_last_at`: "el momento del último registro CON RUTA
                // VÁLIDA" (comentario de la migración), es decir con
                // posición real — no cualquier mensaje, igual que
                // `latestPosition` frente a `latestRoute`. Ningún código lo
                // mantenía al día (el único método que lo leía,
                // `getRecentsAircrafts()`, no tiene ningún caller): se
                // actualiza aquí, en cada sondeo con posición, en vez de
                // depender de un comando aparte.
                $aircraft->route_last_at = $newRoute->seen_at;
                $aircraft->save();
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
        // Ordenamos los registros para que dentro del mismo lote se procesen
        // en orden cronológico ascendente (menor `messages` primero). dump1090
        // suele volcar la tabla de más reciente a más antiguo, lo que causaba
        // que las posiciones más antiguas se insertaran después (mayor `id`).
        usort($records, function (array $a, array $b): int {
            $icaoA = (string) ($a['icao'] ?? '');
            $icaoB = (string) ($b['icao'] ?? '');

            if ($icaoA !== $icaoB) {
                return strcmp($icaoA, $icaoB);
            }

            $msgA = (int) ($a['messages'] ?? 0);
            $msgB = (int) ($b['messages'] ?? 0);

            return $msgA <=> $msgB;
        });

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

        foreach (['squawk', 'flight', 'lat', 'lon', 'altitude', 'vert_rate', 'track', 'speed', 'messages', 'rssi', 'nic', 'rc', 'emergency'] as $field) {
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
        $since = now()->subMinutes($minutes);

        $aircrafts = AirFlightAirPlane::with(['latestRoute', 'latestPosition', 'trail'])
            ->whereHas('routes', function ($query) use ($since) {
                $query->where('seen_at', '>=', $since)
                    ->whereNotNull('lat')
                    ->whereNotNull('lon');
            })
            ->orderByDesc('seen_last_at')
            ->get();

        if ($aircrafts->isEmpty()) {
            return $aircrafts;
        }

        /** @var \Illuminate\Support\Collection<int, object{airplane_id: int|string, flight: ?string, squawk: ?string, altitude: float|int|string|null, speed: float|int|string|null, track: int|string|null, vert_rate: float|int|string|null, emergency: ?string}> $recentValues */
        $recentValues = DB::table('airflight_routes as r')
            ->whereIn('r.airplane_id', $aircrafts->pluck('id'))
            ->where('r.seen_at', '>=', $since)
            ->groupBy('r.airplane_id')
            ->select([
                'r.airplane_id',
                DB::raw('(array_agg(r.flight ORDER BY r.seen_at DESC) FILTER (WHERE r.flight IS NOT NULL))[1] as flight'),
                DB::raw('(array_agg(r.squawk ORDER BY r.seen_at DESC) FILTER (WHERE r.squawk IS NOT NULL))[1] as squawk'),
                DB::raw('(array_agg(r.altitude ORDER BY r.seen_at DESC) FILTER (WHERE r.altitude IS NOT NULL))[1] as altitude'),
                DB::raw('(array_agg(r.speed ORDER BY r.seen_at DESC) FILTER (WHERE r.speed IS NOT NULL))[1] as speed'),
                DB::raw('(array_agg(r.track ORDER BY r.seen_at DESC) FILTER (WHERE r.track IS NOT NULL))[1] as track'),
                DB::raw('(array_agg(r.vert_rate ORDER BY r.seen_at DESC) FILTER (WHERE r.vert_rate IS NOT NULL))[1] as vert_rate'),
                DB::raw('(array_agg(r.emergency ORDER BY r.seen_at DESC) FILTER (WHERE r.emergency IS NOT NULL))[1] as emergency'),
            ])
            ->get()
            ->keyBy(fn ($item) => (int) $item->airplane_id);

        foreach ($aircrafts as $aircraft) {
            $recent = $recentValues->get($aircraft->id);
            if ($recent === null) {
                continue;
            }

            $route = $aircraft->latestRoute;
            if ($route === null) {
                $route = new AirFlightRoute([
                    'airplane_id' => $aircraft->id,
                    'seen_at' => $aircraft->seen_last_at,
                ]);
                $aircraft->setRelation('latestRoute', $route);
            }

            $route->flight ??= $recent->flight;
            $route->squawk ??= $recent->squawk;
            $route->altitude ??= $recent->altitude !== null ? (float) $recent->altitude : null;
            $route->speed ??= $recent->speed !== null ? (float) $recent->speed : null;
            $route->track ??= $recent->track !== null ? (int) $recent->track : null;
            $route->vert_rate ??= $recent->vert_rate !== null ? (float) $recent->vert_rate : null;
            $route->emergency ??= $recent->emergency;
        }

        return $aircrafts;
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
            ]);
    }

    /**
     * Obtiene el conteo de aeronaves detectadas en ventanas temporales:
     * última hora, últimas 24 horas, últimos 7 días y total histórico.
     *
     * @return array<string,int>
     */
    public function getAirFlightStats(): array
    {
        return [
            'last_hour' => (int) Cache::remember('airflight:web:stats:1h', 60, fn () => AirFlightAirPlane::where('seen_last_at', '>=', now()->subHour())->count()),
            'last_24h' => (int) Cache::remember('airflight:web:stats:24h', 300, fn () => AirFlightAirPlane::where('seen_last_at', '>=', now()->subDay())->count()),
            'last_7d' => (int) Cache::remember('airflight:web:stats:7d', 900, fn () => AirFlightAirPlane::where('seen_last_at', '>=', now()->subDays(7))->count()),
            'total' => (int) Cache::remember('airflight:web:stats:total', 3600, fn () => AirFlightAirPlane::count()),
        ];
    }

    /**
     * Obtiene los aviones más frecuentes (con mayor número de días distintos detectados en el receptor).
     *
     * @return Collection<int, AirFlightAirPlane>
     */
    public function getTopAircraft(int $limit = 4): Collection
    {
        return Cache::remember('airflight:web:top_aircraft:'.$limit, 86400, function () use ($limit) {
            $totalRoutes = DB::table('airflight_routes')->count();

            if ($totalRoutes > 10000) {
                /** @var array<int, object{airplane_id: int|string, distinct_days: int|string, total_routes: int|string}> $records */
                $records = DB::select('
                    WITH candidate_planes AS (
                        SELECT airplane_id
                        FROM airflight_routes
                        GROUP BY airplane_id
                        HAVING count(*) >= 100
                    )
                    SELECT r.airplane_id, COUNT(DISTINCT DATE(r.seen_at)) as distinct_days, COUNT(*) as total_routes
                    FROM airflight_routes r
                    INNER JOIN candidate_planes c ON r.airplane_id = c.airplane_id
                    WHERE r.seen_at IS NOT NULL
                    GROUP BY r.airplane_id
                    ORDER BY distinct_days DESC
                    LIMIT :limit
                ', ['limit' => $limit]);
            } else {
                /** @var array<int, object{airplane_id: int|string, distinct_days: int|string, total_routes: int|string}> $records */
                $records = DB::select('
                    SELECT airplane_id, COUNT(DISTINCT DATE(seen_at)) as distinct_days, COUNT(*) as total_routes
                    FROM airflight_routes
                    WHERE seen_at IS NOT NULL
                    GROUP BY airplane_id
                    ORDER BY distinct_days DESC
                    LIMIT :limit
                ', ['limit' => $limit]);
            }

            if (empty($records)) {
                return new Collection;
            }

            $airplaneIds = array_map(fn ($r) => (int) $r->airplane_id, $records);
            $daysMap = [];
            $routesMap = [];
            foreach ($records as $r) {
                $daysMap[(int) $r->airplane_id] = (int) $r->distinct_days;
                $routesMap[(int) $r->airplane_id] = (int) $r->total_routes;
            }

            $planes = AirFlightAirPlane::whereIn('id', $airplaneIds)->get()->keyBy('id');

            $result = new Collection;
            foreach ($airplaneIds as $id) {
                if (isset($planes[$id])) {
                    $plane = $planes[$id];
                    $plane->setAttribute('distinct_days', $daysMap[$id]);
                    $plane->setAttribute('total_routes', $routesMap[$id]);
                    $result->push($plane);
                }
            }

            return $result;
        });
    }
}
