<?php

declare(strict_types=1);

namespace App\Http\Controllers\AirFlight;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\AirFlight\AirFlightResource;
use App\Services\AirFlight\AirFlightService;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Class AirFlightController
 */
class AirFlightController extends Controller
{
    /**
     * Contrato de unidades de `airflight_routes` (definitivo, 2026-09-09 —
     * ver docs/info/airflight.md): el capturador ya sube `altitude` en
     * metros y `speed`/`vert_rate` en m/s, tal cual documenta la migración.
     * No hace falta convertir `altitude` para la tabla "Aviones
     * detectados"; `speed` sí se pasa a km/h, que se lee más cómodo que
     * m/s.
     */
    private const METERS_PER_SECOND_TO_KMH = 3.6;

    /**
     * Aviones activos para el mapa de esta misma web.
     *
     * Vive en el bloque **web** y no en la API a propósito: lo consume el mapa
     * de `/airflight`, que es una página propia, no una integración. Devuelve
     * lo justo que pinta el mapa —los vistos hace poco— y va cacheado unos
     * segundos.
     *
     * Hasta el 2026-09-06 el mapa llamaba a `GET /api/v2/airflight/aircrafts`,
     * y por eso esa ruta tenía que estar abierta al mundo: la ability
     * `airflight:read` no protegía nada. Ahora la API pide token y ofrece a
     * cambio el historial por fechas, filtros y paginación.
     */
    public function aircrafts(Request $request): JsonResponse
    {
        $minutes = max(1, min((int) $request->query('minutes', '10'), 1440));

        // Los aviones se refrescan cada 5 s en el mapa (`refresh` de
        // `receiver()`), así que diez segundos de caché quitan casi todas las
        // consultas sin que se note en pantalla.
        $aviones = Cache::remember(
            'airflight:web:aircrafts:'.$minutes,
            10,
            fn () => AirFlightResource::collection(
                app(AirFlightService::class)->getActiveAircrafts($minutes)
            )->resolve()
        );

        return response()->json(['success' => true, 'data' => $aviones]);
    }

    /**
     * Aviones detectados en la última hora, para refrescar la tabla de esta
     * misma vista sin recargar la página completa.
     *
     * Mismo criterio que `aircrafts()`: vive en el bloque web porque solo la
     * consume el propio `/airflight` desde el navegador. La vista pinta la
     * primera tanda ya en el HTML (sin petición extra al cargar); esto es lo
     * que llama el sondeo cada minuto para refrescarla.
     *
     * Usa `AirFlightService::getDetectedQuery()`: el último valor conocido
     * de cada campo dentro de la ventana, no la última ruta suelta (ver su
     * docblock — Mode S manda cada dato en mensajes distintos).
     */
    public function detected(): JsonResponse
    {
        $lastHour = Carbon::now()->subHour();

        $planes = Cache::remember(
            'airflight:web:detected',
            20,
            fn () => app(AirFlightService::class)->getDetectedQuery($lastHour)
                ->limit(20)
                ->get()
                ->map(fn ($plane) => [
                    'icao' => $plane->icao,
                    'squawk' => $plane->squawk,
                    'flight' => $plane->flight,
                    // `altitude` ya llega en metros, sólo se redondea. `speed`
                    // se pasa de m/s a km/h para la tabla (ver docblock de la
                    // clase).
                    'altitude' => $this->roundOrNull($plane->altitude),
                    'speed' => $this->toKmh($plane->speed),
                    'track' => $plane->track !== null ? (int) $plane->track : null,
                    'seen_last_at' => $this->seenLastAtAsUtcIso($plane->seen_last_at),
                ])
                ->values()
                ->all()
        );

        return response()->json(['success' => true, 'data' => $planes]);
    }

    /**
     * Configuración del receptor para el mapa.
     *
     * Son constantes de pintado (dónde centrar el mapa y cada cuánto refrescar),
     * no datos de nadie.
     */
    public function receiver(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                // No se guardan snapshots temporales para reproducir el
                // historial de recorrido (sólo la última posición por avión),
                // así que se desactiva la reproducción de historial.
                'history' => 0,
                'lat' => 36.7381,
                'lon' => -6.4301,
                'refresh' => 5000,
                'version' => 'api raupulus v2',
            ],
        ]);
    }

    /**
     * Lleva a la vista de resumen para visualizar la depuración.
     *
     * Mismo criterio que `detected()`: el último valor conocido de cada
     * campo dentro de la última hora, no una ruta suelta.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $lastHour = Carbon::now()->subHour();

        $planes = app(AirFlightService::class)->getDetectedQuery($lastHour)->paginate(20);

        // `getDetectedQuery()` no es Eloquent: `seen_last_at` llega tal cual
        // lo guarda Postgres, "2026-09-08 09:29:34" sin marca de zona. El
        // frontend lo convierte a la hora local del navegador (con Madrid de
        // respaldo); para eso necesita un ISO-8601 sin ambigüedad, no esa
        // cadena — algunos navegadores la interpretan como hora local en vez
        // de UTC.
        $planes->getCollection()->transform(function ($plane) {
            $plane->seen_last_at = $this->seenLastAtAsUtcIso($plane->seen_last_at);
            // Igual que en detected(): altitude ya está en metros (sólo se
            // redondea), speed se pasa de m/s a km/h.
            $plane->altitude = $this->roundOrNull($plane->altitude);
            $plane->speed = $this->toKmh($plane->speed);

            return $plane;
        });

        return view('airflight.index')->with([
            'planes' => $planes,
        ]);
    }

    /**
     * `seen_last_at` se guarda en `APP_TIMEZONE` (UTC, ver `config/app.php`)
     * como timestamp sin zona. `Carbon::parse($valor, 'UTC')` le pone la
     * zona explícita antes de formatear a ISO-8601, para que
     * `new Date(...)` en el navegador lo interprete siempre como UTC y no
     * como hora local del propio navegador.
     */
    private function seenLastAtAsUtcIso(?string $value): ?string
    {
        return $value !== null ? Carbon::parse($value, 'UTC')->toISOString() : null;
    }

    /**
     * Redondea al entero para la tabla — no tiene sentido mostrar más
     * precisión de la que se va a leer de un vistazo.
     */
    private function roundOrNull(string|float|null $value): ?int
    {
        return $value !== null ? (int) round((float) $value) : null;
    }

    /**
     * m/s (valor ingerido) a km/h, redondeado al km/h.
     */
    private function toKmh(string|float|null $speedMetersPerSecond): ?int
    {
        return $speedMetersPerSecond !== null
            ? (int) round(((float) $speedMetersPerSecond) * self::METERS_PER_SECOND_TO_KMH)
            : null;
    }
}
