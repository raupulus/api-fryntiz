<?php

declare(strict_types=1);

namespace App\Support\Http;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Throwable;

/**
 * La FORMA del envelope de la API V2, en un único sitio.
 *
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │ HAY DOS PUERTAS DE ENTRADA Y ESTA CLASE ES SU FONDO COMÚN                 │
 * │                                                                           │
 * │  · {@see \JsonHelper}                  — clase estática, llamable desde   │
 * │                                          CUALQUIER sitio: handlers de     │
 * │                                          excepciones, rutas, middleware.  │
 * │  · {@see ApiResponseTrait} — trait, para los controladores.   │
 * │                                                                           │
 * │ Las dos son públicas y las dos se mantienen. Lo que NO se duplica es la   │
 * │ forma del sobre ni el bloque de depuración: eso vive aquí, porque una     │
 * │ lista blanca de cabeceras escrita dos veces es una lista blanca que       │
 * │ algún día sólo se actualiza en una.                                       │
 * │                                                                           │
 * │ `ApiResponseParityTest` comprueba que las dos puertas devuelven byte a    │
 * │ byte lo mismo. Si tocas la forma aquí, ese test es el que avisa.          │
 * └───────────────────────────────────────────────────────────────────────────┘
 *
 * El envelope es `{success, message, data}` y **no se renombra** (D101): lo
 * consumen las ocho webs. Añadir claves es retrocompatible; renombrar `success`
 * a `status` no lo es. Las claves opcionales van siempre detrás y sólo cuando
 * hay algo que poner:
 *
 *   {success, message, data}                    éxito
 *   {success, message, data, meta}              colección paginada
 *   {success, message, data, warnings}          se guardó, pero mira esto
 *   {success, message, errors}                  422
 *   {success, message, ..., debug}              sólo con APP_DEBUG=true
 */
final class ApiEnvelope
{
    /**
     * Cabeceras que pueden aparecer en el bloque `debug`.
     *
     * Es una **lista blanca**, no una lista negra, y la diferencia importa: con
     * lista negra, la cabecera que se invente mañana entra sola. Aquí no entra
     * nada que no esté escrito abajo.
     *
     * Fuera quedan a propósito `authorization` (lleva el Bearer del token),
     * `cookie` y `set-cookie` (la sesión del panel), `x-xsrf-token`,
     * `php-auth-*` y `proxy-authorization`. El bloque `debug` sólo sale en
     * desarrollo, y desarrollo es justo donde se pegan respuestas en capturas y
     * en tickets: que un token de dispositivo salga ahí es exactamente el
     * accidente que hay que evitar.
     *
     * @var list<string>
     */
    public const SAFE_HEADERS = [
        'accept',
        'accept-language',
        'content-type',
        'content-length',
        'host',
        'origin',
        'referer',
        'user-agent',
        'x-request-id',
        'x-requested-with',
    ];

    /**
     * Campos del cuerpo cuyo valor NUNCA se vuelca en `debug`.
     *
     * `parameters` viene de `$request->all()`, así que sin esto el bloque de
     * depuración de `POST /auth/tokens` enseñaría la contraseña en claro.
     *
     * @var list<string>
     */
    public const REDACTED_INPUT = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'secret',
        'api_key',
        'g-recaptcha-response',
        'recaptchaToken',
    ];

    /** Lo que se pone en lugar del valor de un campo sensible. */
    public const REDACTED_PLACEHOLDER = '[oculto]';

    /**
     * Sobre de una respuesta correcta.
     *
     * @param  array<string,mixed>  $extra  Claves opcionales (`meta`, `warnings`).
     * @return array<string,mixed>
     */
    public static function success(mixed $data, string $message, array $extra = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ] + $extra + self::debugSection();
    }

    /**
     * Sobre de una respuesta de error.
     *
     * `data` no aparece: en un error no hay recurso que devolver, y una clave
     * `data: null` invita a que el cliente la lea igualmente.
     *
     * @param  array<string,mixed>  $extra  Claves opcionales (`errors`).
     * @return array<string,mixed>
     */
    public static function error(string $message, array $extra = [], ?Throwable $exception = null): array
    {
        return [
            'success' => false,
            'message' => $message,
        ] + $extra + self::debugSection($exception);
    }

    /**
     * Bloque `debug`, o nada.
     *
     * Sólo existe con `APP_DEBUG=true`. En producción esta función devuelve un
     * array vacío y la clave no llega a aparecer en el JSON.
     *
     * Es lo que la V1 hacía en `JsonHelper::siteData()`, con dos diferencias:
     * las cabeceras pasan por lista blanca y los campos sensibles del cuerpo se
     * tapan. La versión de la V1 volcaba `headers->all()` entero.
     *
     * @return array{debug?: array<string,mixed>}
     */
    public static function debugSection(?Throwable $exception = null): array
    {
        if (! config('app.debug')) {
            return [];
        }

        $request = request();

        $debug = [
            'method' => $request?->method(),
            'domain' => $request?->getHost(),
            'path' => $request?->path(),
            'full_url' => $request?->fullUrl(),
            'locale' => app()->getLocale(),
            'parameters' => self::redact($request?->all() ?? []),
            'headers' => self::safeHeaders($request),
        ];

        if ($exception instanceof Throwable) {
            $debug['exception'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return ['debug' => $debug];
    }

    /**
     * Las cabeceras de la petición que están en la lista blanca.
     *
     * @return array<string,string>
     */
    private static function safeHeaders(?Request $request): array
    {
        if (! $request instanceof Request) {
            return [];
        }

        $headers = [];

        foreach (self::SAFE_HEADERS as $name) {
            $value = $request->headers->get($name);

            if ($value !== null && $value !== '') {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Tapa el valor de los campos sensibles, a cualquier profundidad.
     *
     * @param  array<mixed>  $input
     * @return array<mixed>
     */
    private static function redact(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), self::REDACTED_INPUT, true)) {
                $input[$key] = self::REDACTED_PLACEHOLDER;

                continue;
            }

            if (is_array($value)) {
                $input[$key] = self::redact($value);
            }
        }

        return $input;
    }
}
