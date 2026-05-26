<?php

namespace App\Services\WeatherStation;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de integración con la API OpenData de AEMET.
 *
 * Implementa retry con backoff exponencial, caché por endpoint y control de
 * rate-limit para evitar exceder los ~100 req/min de la API.
 *
 * Documentación: https://opendata.aemet.es/dist/index.html
 */
class AEMETService
{
    private string $apiKey;
    private string $baseUrl;
    private int $retryAttempts;
    private int $retryBaseDelayMs;

    public function __construct()
    {
        $this->apiKey = config('aemet.api_key', config('services.aemet.api_key', ''));
        $this->baseUrl = config('aemet.base_url', 'https://opendata.aemet.es/opendata/api');
        $this->retryAttempts = (int) config('aemet.rate_limit.retry_attempts', 3);
        $this->retryBaseDelayMs = (int) config('aemet.rate_limit.retry_base_delay_ms', 1000);
    }

    // ───────────────────────── Predicciones ─────────────────────────

    /**
     * Predicción diaria por municipio.
     */
    public function getDailyPrediction(?string $municipioCode = null): ?array
    {
        $code = $municipioCode ?? config('aemet.default_municipio');
        return $this->cachedRequest(
            "/prediccion/especifica/municipio/diaria/{$code}",
            'daily_prediction',
            "aemet:daily_prediction:{$code}"
        );
    }

    /**
     * Predicción específica por playa (zonas costeras).
     */
    public function getBeachPrediction(?string $beachCode = null): ?array
    {
        $code = $beachCode ?? config('aemet.default_playa');
        return $this->cachedRequest(
            "/prediccion/especifica/playa/{$code}",
            'prediction_beach',
            "aemet:beach:{$code}"
        );
    }

    /**
     * Predicción marítima costera.
     */
    public function getCoastPrediction(?string $coastCode = null): ?array
    {
        $code = $coastCode ?? config('aemet.default_costa');
        return $this->cachedRequest(
            "/prediccion/maritima/costera/costa/{$code}",
            'coast',
            "aemet:coast:{$code}"
        );
    }

    /**
     * Predicción marítima de alta mar.
     */
    public function getHighSeaPrediction(?string $areaCode = null): ?array
    {
        $code = $areaCode ?? config('aemet.default_area');
        return $this->cachedRequest(
            "/prediccion/maritima/altamar/area/{$code}",
            'high_sea',
            "aemet:high_sea:{$code}"
        );
    }

    // ─────────────────────── Calidad del aire ───────────────────────

    /**
     * Datos de contaminación de fondo.
     */
    public function getContamination(): ?array
    {
        return $this->cachedRequest(
            '/red/especial/contaminacionfondo',
            'contamination',
            'aemet:contamination'
        );
    }

    /**
     * Datos de ozono troposférico.
     */
    public function getOzone(): ?array
    {
        return $this->cachedRequest(
            '/red/especial/ozono',
            'ozone',
            'aemet:ozone'
        );
    }

    /**
     * Datos de radiación solar.
     */
    public function getSunRadiation(): ?array
    {
        return $this->cachedRequest(
            '/red/especial/radiacionsolar',
            'sun_radiation',
            'aemet:sun_radiation'
        );
    }

    // ───────────────────────── Avisos ─────────────────────────────

    /**
     * Avisos CAP (eventos meteorológicos adversos) por área.
     */
    public function getAdverseEvents(?string $areaCode = null): ?array
    {
        $code = $areaCode ?? config('aemet.default_area');
        return $this->cachedRequest(
            "/avisos_cap/ultimoelaborado/area/{$code}",
            'adverse_events',
            "aemet:adverse:{$code}"
        );
    }

    // ─────────────────────── Implementación interna ─────────────────

    /**
     * Wrap una llamada HTTP con caché por TTL configurado por tipo de endpoint.
     */
    private function cachedRequest(string $endpoint, string $ttlKey, string $cacheKey): ?array
    {
        $ttl = (int) config("aemet.cache_ttl.{$ttlKey}", 600);
        return Cache::remember($cacheKey, $ttl, fn () => $this->makeRequest($endpoint));
    }

    /**
     * Petición a AEMET con retry/backoff y validación del flujo de 2 saltos
     * (envelope con clave "datos" → URL real con el payload).
     */
    private function makeRequest(string $endpoint): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('AEMET: no se ha configurado AEMET_API_KEY.');
            return null;
        }

        try {
            $primary = $this->httpWithRetry()
                ->withHeaders(['api_key' => $this->apiKey])
                ->get($this->baseUrl . $endpoint);

            if (! $primary->successful()) {
                Log::warning('AEMET: respuesta no exitosa', [
                    'endpoint' => $endpoint,
                    'status' => $primary->status(),
                ]);
                return null;
            }

            $envelope = $primary->json();
            if (! is_array($envelope) || ! isset($envelope['datos'])) {
                Log::warning('AEMET: envelope sin clave "datos"', [
                    'endpoint' => $endpoint,
                ]);
                return null;
            }

            $secondary = $this->httpWithRetry()->get($envelope['datos']);
            if (! $secondary->successful()) {
                Log::warning('AEMET: datos no descargables', [
                    'datos_url' => $envelope['datos'],
                    'status' => $secondary->status(),
                ]);
                return null;
            }

            $data = $secondary->json();
            if (! is_array($data)) {
                Log::warning('AEMET: payload no es JSON array', ['endpoint' => $endpoint]);
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('AEMET: excepción durante la petición', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Devuelve un PendingRequest con retry y backoff exponencial.
     * Retentea en HTTP 429 (Too Many Requests) y 5xx.
     */
    private function httpWithRetry(): PendingRequest
    {
        return Http::retry(
            $this->retryAttempts,
            $this->retryBaseDelayMs,
            function (\Throwable $exception) {
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    $status = $exception->response?->status();
                    return $status === 429 || ($status >= 500 && $status < 600);
                }
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            },
            throw: false
        );
    }
}
