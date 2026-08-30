<?php

declare(strict_types=1);

namespace App\Services\WeatherStation;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de integración con la API OpenData de AEMET.
 *
 * Lectura previa obligatoria antes de tocar esto: `docs/apis/aemet/00-fundamentos.md`,
 * `ERRATAS.md` y `LIMITACIONES.md`. Está verificado con peticiones reales y explica
 * por qué este código hace cosas que parecen raras.
 *
 * Las cuatro que más importan:
 *
 * 1. **Dos saltos.** El endpoint devuelve un sobre; los datos están en la URL `datos`,
 *    sin autenticación y efímera. Se consume una vez y se persiste.
 * 2. **Codificación variable.** La mayoría responde en ISO-8859-15 y algunos productos
 *    en UTF-8 real. Hay que LEER el charset de la cabecera: `json_decode` exige UTF-8 y
 *    con ISO-8859-15 devuelve `null` **sin lanzar excepción** — era el bug N296, que
 *    dejaba el módulo entero sin funcionar.
 * 3. **Un 200 no significa que haya datos.** Puede traer `estado: 404` en el cuerpo,
 *    venir vacío (falta la api_key) o traer datos de hace años.
 * 4. **Cuota indocumentada** en la cabecera `Remaining-request-endpoint`: 40 por
 *    plantilla de endpoint, ligada a la IP, y el 429 no trae `Retry-After`.
 */
class AEMETService
{
    private string $apiKey;

    private string $baseUrl;

    private int $retryAttempts;

    private int $retryBaseDelayMs;

    /**
     * Charset por defecto cuando la cabecera no lo declara.
     * La mayoría de la API responde en ISO-8859-15.
     */
    private const DEFAULT_CHARSET = 'ISO-8859-15';

    /**
     * Por debajo de estas peticiones restantes se deja de pedir a ese endpoint
     * hasta la siguiente ventana. `Remaining-request-endpoint` no garantiza que
     * la siguiente petición funcione, así que se deja margen.
     */
    private const QUOTA_MARGIN = 5;

    public function __construct()
    {
        $this->apiKey = config('aemet.api_key', config('services.aemet.api_key', ''));
        $this->baseUrl = config('aemet.base_url', 'https://opendata.aemet.es/opendata/api');
        $this->retryAttempts = (int) config('aemet.rate_limit.retry_attempts', 2);
        $this->retryBaseDelayMs = (int) config('aemet.rate_limit.retry_base_delay_ms', 30000);
    }

    // ───────────────────────── Predicciones ─────────────────────────

    /**
     * Predicción diaria por municipio.
     */
    public function getDailyPrediction(?string $municipioCode = null): ?array
    {
        $code = $municipioCode ?? config('aemet.default_municipality');

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
        $code = $beachCode ?? config('aemet.default_beach');

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
        $code = $coastCode ?? config('aemet.default_coast');

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
     * Una consulta completa a AEMET: los dos saltos, con todas las validaciones
     * que la API necesita y que un `$response->successful()` no cubre.
     */
    /**
     * Una sola petición cruda a una URL de AEMET, con todo el endurecimiento
     * de esta clase: clave en cabecera, charset respetado, cuerpo vacío y HTML
     * de error detectados, timeout y reintentos acotados, y cuenta de cuota.
     *
     * Existe porque `support/helpers/AEMETHelper.php` —que es **el código que
     * de verdad usan los comandos**— hacía su propia petición con cURL a pelo y
     * `json_decode(..., JSON_INVALID_UTF8_SUBSTITUTE)`, que parsea sin fallar y
     * deja todos los acentos destruidos. Ahora el helper delega aquí.
     *
     * El helper hace los dos saltos por su cuenta (sobre → `datos`), así que
     * este método no interpreta el sobre: devuelve lo que haya.
     *
     * @param  bool  $comoJson  false para los productos que vienen en texto
     *                          plano (contaminación, radiación solar…).
     * @return array<mixed>|string|null
     */
    public function fetchRaw(string $url, bool $comoJson = true): array|string|null
    {
        if ($this->apiKey === '') {
            // Sin clave AEMET devuelve 200 con el cuerpo VACÍO, no un 401: si no
            // se comprueba aquí, el fallo es indistinguible de "no hay datos".
            Log::warning('AEMET: no se ha configurado AEMET_API_KEY.');

            return null;
        }

        $isFromAemet = str_starts_with($url, $this->baseUrl);
        $endpoint = $isFromAemet ? substr($url, strlen($this->baseUrl)) : $url;

        if ($isFromAemet && $this->withoutQuota($endpoint)) {
            Log::info('AEMET: endpoint en pausa por cuota agotada', ['endpoint' => $endpoint]);

            return null;
        }

        try {
            $request = $this->httpWithRetry();

            // La URL de `datos` es efímera y NO lleva autenticación. Mandarle la
            // clave sería filtrarla a un host que no la necesita.
            if ($isFromAemet) {
                $request = $request->withHeaders(['api_key' => $this->apiKey]);
            }

            $response = $request->get($url);

            if ($isFromAemet) {
                $this->recordQuotaUsage($endpoint, $response->header('Remaining-request-endpoint'));
            }

            if (! $comoJson) {
                return $this->bodyAsUtf8($response, $endpoint);
            }

            return $this->decodeJson($response, $endpoint);
        } catch (\Throwable $e) {
            Log::error('AEMET: excepción durante la petición', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Descarga un cuerpo **binario** tal cual, sin tocar un solo byte.
     *
     * Existe por los avisos CAP: vienen en un `tar` cuyo `Content-Type` es
     * `application/x-gtar;charset=ISO-8859-15`. Ese `charset` en un binario no
     * significa nada, pero `fetchRaw(..., false)` se lo cree y le pasa el
     * cuerpo por `mb_convert_encoding()`: cada byte ≥ 0x80 cambia y el tar deja
     * de abrirse. Un binario no tiene codificación que convertir.
     *
     * La URL de `datos` es efímera y no lleva autenticación, así que aquí no se
     * manda la clave salvo que la URL sea del dominio de la API.
     */
    public function fetchBinary(string $url): ?string
    {
        $isFromAemet = str_starts_with($url, $this->baseUrl);
        $endpoint = $isFromAemet ? substr($url, strlen($this->baseUrl)) : $url;

        if ($isFromAemet && $this->apiKey === '') {
            Log::warning('AEMET: no se ha configurado AEMET_API_KEY.');

            return null;
        }

        try {
            $request = $this->httpWithRetry();

            if ($isFromAemet) {
                $request = $request->withHeaders(['api_key' => $this->apiKey]);
            }

            $response = $request->get($url);

            if ($isFromAemet) {
                $this->recordQuotaUsage($endpoint, $response->header('Remaining-request-endpoint'));
            }

            $body = $response->body();

            if ($body === '') {
                Log::warning('AEMET: descarga binaria vacía', ['endpoint' => $endpoint]);

                return null;
            }

            // AEMET responde 200 con una página de error HTML más a menudo de lo
            // que parece. Un binario nunca empieza por `<`.
            if (str_starts_with($body, '<')) {
                Log::warning('AEMET: la descarga binaria es HTML de error, no datos', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $body;
        } catch (\Throwable $e) {
            Log::error('AEMET: excepción durante la descarga binaria', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cuerpo de la respuesta convertido a UTF-8, sin intentar parsearlo.
     *
     * Para los productos que no son JSON: contaminación viene en formato FINN y
     * radiación solar en un texto tabulado, ambos en ISO-8859-15.
     *
     * @param  Response  $response
     */
    private function bodyAsUtf8($response, string $endpoint): ?string
    {
        $body = $response->body();

        if (trim($body) === '') {
            Log::warning('AEMET: respuesta vacía (revisa AEMET_API_KEY)', ['endpoint' => $endpoint]);

            return null;
        }

        if (str_starts_with(ltrim($body), '<')) {
            Log::warning('AEMET: la respuesta es HTML de error, no datos', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return null;
        }

        $charset = $this->charsetOf($response->header('Content-Type'));

        if ($charset !== 'UTF-8') {
            $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

            if (is_string($converted)) {
                return $converted;
            }
        }

        return $body;
    }

    private function makeRequest(string $endpoint): ?array
    {
        if ($this->apiKey === '') {
            // Sin clave, AEMET devuelve 200 con el cuerpo VACÍO — no un 401.
            // Si no se comprueba aquí, el fallo es indistinguible de "no hay datos".
            Log::warning('AEMET: no se ha configurado AEMET_API_KEY.');

            return null;
        }

        if ($this->withoutQuota($endpoint)) {
            Log::info('AEMET: endpoint en pausa por cuota agotada', ['endpoint' => $endpoint]);

            return null;
        }

        try {
            // ── PASO 1 ── el sobre. Con la clave en CABECERA, nunca en la query:
            // una credencial en la URL acaba en los logs del servidor y del proxy.
            $sobre = $this->httpWithRetry()
                ->withHeaders(['api_key' => $this->apiKey])
                ->get($this->baseUrl.$endpoint);

            $this->recordQuotaUsage($endpoint, $sobre->header('Remaining-request-endpoint'));

            $envelope = $this->decodeJson($sobre, $endpoint);

            if ($envelope === null) {
                return null;
            }

            // El código que importa es el del CUERPO, no el HTTP.
            $code = (int) ($envelope['estado'] ?? 0);

            if ($code !== 200) {
                // `estado` y `descripcion` son campos de AEMET, no nuestros:
                // se leen tal y como los manda su API. Las claves del contexto
                // del log sí van en inglés, como el resto de los logs.
                Log::warning('AEMET: envelope did not come with status 200', [
                    'endpoint' => $endpoint,
                    'status' => $code,
                    'description' => $envelope['descripcion'] ?? null,
                ]);

                return null;
            }

            if (! isset($envelope['datos']) || ! is_string($envelope['datos'])) {
                Log::warning('AEMET: sobre con estado 200 y sin URL de datos', [
                    'endpoint' => $endpoint,
                ]);

                return null;
            }

            // ── PASO 2 ── los datos. SIN autenticación, y la URL es efímera:
            // se consume aquí y no se guarda en ningún sitio.
            $data = $this->httpWithRetry()->get($envelope['datos']);

            if (! $data->successful()) {
                Log::warning('AEMET: la URL de datos no responde', [
                    'endpoint' => $endpoint,
                    'status' => $data->status(),
                ]);

                return null;
            }

            $payload = $this->decodeJson($data, $endpoint);

            if ($payload === null) {
                return null;
            }

            $this->warnIfStale($endpoint, $payload);

            return $payload;
        } catch (\Throwable $e) {
            Log::error('AEMET: excepción durante la petición', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Decodifica una respuesta de AEMET **respetando el charset que declara**.
     *
     * Este método es el arreglo de N296. Antes se hacía `$response->json()`, que por
     * debajo es `json_decode`, y `json_decode` **exige UTF-8**: con los bytes
     * ISO-8859-15 que devuelve AEMET retorna `null` sin lanzar ninguna excepción.
     * Resultado: el módulo entero devolvía null en cuanto el payload traía una tilde,
     * y siempre la trae — el bloque `origen` dice "Meteorología".
     *
     * No se puede convertir a ciegas: ozono, radiación solar y el resumen
     * climatológico vienen en **UTF-8 real** y convertirlos los corrompe.
     *
     * Y nada de `JSON_INVALID_UTF8_SUBSTITUTE`: parsea sin fallar y deja todos los
     * acentos destruidos, que es peor porque parece que funciona.
     *
     * @param  Response  $response
     */
    private function decodeJson($response, string $endpoint): ?array
    {
        $body = $response->body();

        // Cuerpo vacío: o falta la clave, o el periodo pedido no existe.
        if (trim($body) === '') {
            Log::warning('AEMET: respuesta vacía (revisa AEMET_API_KEY)', ['endpoint' => $endpoint]);

            return null;
        }

        // Una ruta inexistente devuelve HTML de Tomcat, no JSON.
        if (str_starts_with(ltrim($body), '<')) {
            Log::warning('AEMET: la respuesta es HTML de error, no JSON', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return null;
        }

        $charset = $this->charsetOf($response->header('Content-Type'));

        if ($charset !== 'UTF-8') {
            $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

            if (is_string($converted)) {
                $body = $converted;
            }
        }

        $data = json_decode($body, true);

        if (! is_array($data)) {
            Log::warning('AEMET: el payload no es JSON', [
                'endpoint' => $endpoint,
                'charset' => $charset,
                'json_error' => json_last_error_msg(),
            ]);

            return null;
        }

        return $data;
    }

    /**
     * Saca el charset de la cabecera `Content-Type`.
     *
     * AEMET lo declara de formas distintas según el producto
     * (`text/plain;charset=ISO-8859-15`, `text/plain; charset=UTF-8`), y en las
     * imágenes declara un charset que no significa nada.
     */
    private function charsetOf(?string $contentType): string
    {
        if ($contentType === null) {
            return self::DEFAULT_CHARSET;
        }

        if (preg_match('/charset\s*=\s*"?([A-Za-z0-9_-]+)"?/i', $contentType, $matches) !== 1) {
            return self::DEFAULT_CHARSET;
        }

        $charset = strtoupper(trim($matches[1]));

        return $charset === 'UTF8' ? 'UTF-8' : $charset;
    }

    /**
     * Registra en el log si el contenido es viejo.
     *
     * Hay endpoints de AEMET que devuelven contenido de años atrás con un 200
     * impecable: `/prediccion/provincia/hoy/36` devolvía una predicción de 2022.
     * Nada en la respuesta HTTP lo delata; hay que mirar la fecha del contenido.
     *
     * `elaborado` está en la raíz en unos productos y dentro de `origen` en otros.
     *
     * @param  array<mixed>  $payload
     */
    private function warnIfStale(string $endpoint, array $payload): void
    {
        $first = $payload[0] ?? $payload;

        if (! is_array($first)) {
            return;
        }

        $built = $first['elaborado'] ?? ($first['origen']['elaborado'] ?? null);

        if (! is_string($built) || $built === '') {
            return;
        }

        try {
            $date = Carbon::parse($built);
        } catch (\Throwable) {
            return;
        }

        $days = $date->diffInDays(now());

        if ($days > (int) config('aemet.max_age_days', 3)) {
            Log::warning('AEMET: el contenido está rancio', [
                'endpoint' => $endpoint,
                'elaborado' => $built,
                'days' => $days,
            ]);
        }
    }

    /**
     * ¿Está agotada la cuota de este endpoint?
     *
     * La cuota va por **plantilla de endpoint**, no por URL: cambiar el parámetro
     * no da un cubo nuevo. Y va ligada a la IP además de a la clave, así que
     * generar otra API Key no desbloquea nada.
     */
    private function withoutQuota(string $endpoint): bool
    {
        $remaining = Cache::get($this->quotaKey($endpoint));

        return is_int($remaining) && $remaining <= self::QUOTA_MARGIN;
    }

    /**
     * Guarda lo que dice `Remaining-request-endpoint`, la cabecera indocumentada
     * con la que AEMET expone la cuota. Desaparece justo en el 429, así que sólo
     * sirve para frenar ANTES de agotarla.
     */
    private function recordQuotaUsage(string $endpoint, ?string $remaining): void
    {
        if ($remaining === null || ! is_numeric($remaining)) {
            return;
        }

        Cache::put(
            $this->quotaKey($endpoint),
            (int) $remaining,
            (int) config('aemet.quota_window_seconds', 3600)
        );
    }

    /**
     * La clave de caché es la PLANTILLA del endpoint, con los parámetros
     * sustituidos: `/municipio/diaria/11015` y `/municipio/diaria/28079`
     * comparten cubo.
     */
    private function quotaKey(string $endpoint): string
    {
        $plantilla = preg_replace('#/[0-9][0-9A-Za-z,._-]*$#', '/{param}', $endpoint) ?? $endpoint;

        return 'aemet:cuota:'.md5($plantilla);
    }

    /**
     * Cliente HTTP con reintento.
     *
     * El backoff es **largo y a ciegas** a propósito: el 429 de AEMET no trae
     * `Retry-After`, su mensaje dice "vuelva a intentarlo el próximo minuto" y
     * está medido que **no se cumple** — un endpoint agotado seguía dando 429
     * más de una hora después. Reintentar cada segundo sólo gasta cuota.
     *
     * Y sólo se reintenta en 429 y 5xx: un 401 es la clave caducada y un 404 de
     * ruta no se arregla insistiendo.
     */
    private function httpWithRetry(): PendingRequest
    {
        return Http::timeout((int) config('aemet.timeout_seconds', 30))
            ->retry(
                $this->retryAttempts,
                $this->retryBaseDelayMs,
                function (\Throwable $exception) {
                    if ($exception instanceof RequestException) {
                        $status = $exception->response?->status();

                        return $status === 429 || ($status >= 500 && $status < 600);
                    }

                    return $exception instanceof ConnectionException;
                },
                throw: false
            );
    }
}
