<?php

declare(strict_types=1);

namespace App\Services\Gdacs;

use App\Enums\GdacsAlertLevelEnum;
use App\Enums\GdacsEventTypeEnum;
use App\Models\Gdacs\GdacsEvent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sondea la API de GDACS y guarda sólo los sucesos dentro del radio de
 * interés configurado (`config('gdacs.reference')`).
 *
 * No hay filtro de radio en la API de GDACS (comprobado: `bbox`/`boundingBox`
 * los ignora en silencio), así que la distancia se calcula aquí con
 * haversine contra cada suceso que devuelve el filtro por país. Detalle
 * completo de lo comprobado contra la API real en `docs/future/gdacs-api.md`.
 */
class GdacsService
{
    /**
     * Radio de la Tierra en km, para la fórmula de haversine.
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Sondea GDACS y guarda (o actualiza) los sucesos dentro del radio.
     *
     * @return array{fetched: int, kept: int}
     */
    public function sync(): array
    {
        $payload = $this->fetch();

        if ($payload === null) {
            return ['fetched' => 0, 'kept' => 0];
        }

        $features = $payload['features'] ?? [];
        $kept = 0;

        foreach ($features as $feature) {
            if ($this->persistIfWithinRadius($feature)) {
                $kept++;
            }
        }

        return ['fetched' => count($features), 'kept' => $kept];
    }

    /**
     * Llama a `SEARCH`, ya filtrado por país, tipos y niveles de alerta.
     *
     * @return array<string, mixed>|null Null si la petición falla; se registra en el log.
     */
    private function fetch(): ?array
    {
        $params = [
            'eventlist' => implode(';', config('gdacs.event_types')),
            'alertlevel' => implode(';', config('gdacs.alert_levels')),
            'country' => config('gdacs.country'),
            'fromdate' => Carbon::now()->subDays((int) config('gdacs.lookback_days'))->toDateString(),
            'todate' => Carbon::now()->toDateString(),
        ];

        try {
            $response = Http::timeout((int) config('gdacs.timeout_seconds'))
                ->retry(2, 2000, function (\Throwable $exception) {
                    if ($exception instanceof RequestException) {
                        $status = $exception->response?->status();

                        return $status !== null && $status >= 500 && $status < 600;
                    }

                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->get(config('gdacs.base_url'), $params);
        } catch (\Throwable $e) {
            Log::error('GDACS: excepción al pedir el listado de sucesos', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        // 204: la propia API responde así cuando el filtro no encuentra nada.
        // No es un fallo, es "sin novedades" — se distingue del resto para no
        // ensuciar el log a diario.
        if ($response->status() === 204) {
            return ['features' => []];
        }

        if (! $response->successful()) {
            Log::warning('GDACS: respuesta no exitosa al pedir el listado de sucesos', [
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['features']) || ! is_array($data['features'])) {
            Log::warning('GDACS: respuesta con forma inesperada (sin "features")');

            return null;
        }

        return $data;
    }

    /**
     * Calcula la distancia al punto de referencia y guarda el suceso sólo si
     * cae dentro del radio configurado.
     *
     * @param  array<string, mixed>  $feature  Un elemento de "features" del GeoJSON de GDACS.
     */
    private function persistIfWithinRadius(array $feature): bool
    {
        $properties = $feature['properties'] ?? [];
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        // GeoJSON: [lon, lat], en ese orden. Sin coordenadas no hay nada que
        // medir ni que guardar.
        if (! is_array($coordinates) || count($coordinates) < 2) {
            return false;
        }

        [$lon, $lat] = $coordinates;

        $eventType = GdacsEventTypeEnum::tryFrom((string) ($properties['eventtype'] ?? ''));
        $alertLevel = GdacsAlertLevelEnum::tryFrom((string) ($properties['alertlevel'] ?? ''));
        $eventId = $properties['eventid'] ?? null;

        // Falla cerrado: si GDACS devuelve un tipo o un nivel fuera del
        // catálogo que se pidió, o falta el id, se ignora en vez de guardar
        // algo a medias.
        if ($eventType === null || $alertLevel === null || $eventId === null) {
            return false;
        }

        $reference = config('gdacs.reference');
        $distanceKm = self::distanceKm(
            (float) $reference['lat'],
            (float) $reference['lon'],
            (float) $lat,
            (float) $lon,
        );

        if ($distanceKm > (float) $reference['radius_km']) {
            return false;
        }

        $severity = $properties['severitydata'] ?? [];

        GdacsEvent::updateOrCreate(
            [
                'event_type' => $eventType->value,
                'event_id' => (int) $eventId,
            ],
            [
                'episode_id' => $properties['episodeid'] ?? null,
                'name' => $properties['name'] ?? null,
                'alert_level' => $alertLevel->value,
                // GDACS lo manda como cadena "true"/"false", no como booleano.
                'is_current' => ($properties['iscurrent'] ?? 'false') === 'true',
                'from_date' => $properties['fromdate'] ?? null,
                'to_date' => $properties['todate'] ?? null,
                'last_modified_at' => $properties['datemodified'] ?? Carbon::now(),
                'lat' => (float) $lat,
                'lon' => (float) $lon,
                'distance_km' => $distanceKm,
                'severity_value' => $severity['severity'] ?? null,
                'severity_unit' => $severity['severityunit'] ?? null,
                'severity_text' => $severity['severitytext'] ?? null,
                // La población afectada no viene en este listado — hace falta
                // una llamada aparte por evento (`geteventdata`) que hoy no se
                // hace. Se deja el campo para cuando se implemente.
                'affected_population' => null,
                'report_url' => $properties['url']['report'] ?? null,
            ],
        );

        return true;
    }

    /**
     * Distancia entre dos puntos (grados decimales WGS84) en kilómetros.
     */
    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
