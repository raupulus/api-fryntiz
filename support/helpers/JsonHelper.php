<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * Date: 22/05/2021
 * Time: 18:19
 *
 * @author Raúl Caro Pastorino
 * @copyright Copyright © 2021 Raúl Caro Pastorino
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

use App\Support\Http\ApiEnvelope;
use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuestas JSON de la plataforma, como clase estática.
 *
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │ ⚠️  ESTA CLASE Y {@see ApiResponseTrait} SON HERMANAS.        │
 * │                                                                           │
 * │ Devuelven EXACTAMENTE lo mismo y se mantienen las dos a propósito:        │
 * │                                                                           │
 * │  · El **trait** lo usan los controladores (79 llamadas). Es lo cómodo     │
 * │    dentro de una clase que lo pueda declarar.                             │
 * │  · Este **helper** es estático, así que llega donde el trait no puede:    │
 * │    los handlers de `bootstrap/app.php`, la ruta de cierre de              │
 * │    `routes/api/v2.php` y los `render()` de `app/Exceptions/`. Ésos son    │
 * │    los sitios donde el envelope estaba copiado a mano once veces.         │
 * │                                                                           │
 * │ 👉 SI TOCAS UN MÉTODO DE AQUÍ, TOCA SU GEMELO EN EL TRAIT.                │
 * │                                                                           │
 * │ La forma del sobre NO está escrita en ninguno de los dos: vive en         │
 * │ {@see ApiEnvelope}, que es de donde beben ambos. Y                        │
 * │ `tests/Feature/Api/V2/ApiResponseParityTest.php` compara las dos salidas  │
 * │ método a método: si se separan, la suite se pone roja.                    │
 * └───────────────────────────────────────────────────────────────────────────┘
 *
 * Correspondencia de nombres:
 *
 * | JsonHelper (estático)   | ApiResponseTrait (controladores) |
 * |-------------------------|----------------------------------|
 * | `success()`             | `successResponse()`              |
 * | `created()`             | `createdResponse()`              |
 * | `paginated()`           | `paginatedResponse()`            |
 * | `deleted()`             | `deletedResponse()`              |
 * | `withWarnings()`        | `withWarnings()`                 |
 * | `error()` / `failed()`  | `errorResponse()`                |
 * | `notFound()`            | `notFoundResponse()`             |
 * | `unauthorized()`        | `unauthorizedResponse()`         |
 * | `forbidden()`           | `forbiddenResponse()`            |
 * | `conflict()`            | `conflictResponse()`             |
 * | `serverError()`         | — (sólo el handler la necesita)  |
 *
 * NOTA SOBRE EL FORMATO ANTERIOR (V1). Hasta la revisión de 2026-09-02 esta
 * clase devolvía `{status: 'ok'|'ko', source: {...}, error: {...}}`, que es un
 * contrato distinto del que usa la API V2. Con la migración quedó sin usarse en
 * `/api/v2/*` —los `render()` de las excepciones la saltaban con un
 * `if ($request->is('api/v2/*'))`— y su único uso «vivo»,
 * `KeyCounterController::getKeyboardDataAjax()`, ni siquiera tenía ruta
 * registrada y además envolvía dos veces la respuesta. No había un solo
 * consumidor del formato antiguo, ni en el frontend ni en los tests, así que se
 * alinea con el envelope en lugar de mantener dos contratos vivos.
 *
 * Lo que sí se recupera de la V1 es el bloque de contexto de `siteData()`:
 * ahora es la clave `debug`, sale sólo con `APP_DEBUG=true`, las cabeceras
 * pasan por lista blanca y los campos sensibles del cuerpo van tapados.
 */
class JsonHelper
{
    /**
     * Respuesta correcta con datos.
     *
     * Gemela de `ApiResponseTrait::successResponse()`.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operación exitosa',
        int $status = 200
    ): JsonResponse {
        return response()->json(ApiEnvelope::success($data, $message), $status);
    }

    /**
     * Recurso creado (201), con `Location` opcional.
     *
     * Gemela de `ApiResponseTrait::createdResponse()`.
     */
    public static function created(
        mixed $data = null,
        string $message = 'Recurso creado correctamente',
        ?string $location = null
    ): JsonResponse {
        $response = self::success($data, $message, 201);

        return $location === null ? $response : $response->header('Location', $location);
    }

    /**
     * Petición aceptada para proceso posterior (202).
     */
    public static function accepted(
        mixed $data = null,
        string $message = 'Petición aceptada'
    ): JsonResponse {
        return self::success($data, $message, 202);
    }

    /**
     * Recurso actualizado (200).
     *
     * Devolvía 202 en la V1. Un `PUT` que ya ha escrito no está «aceptado para
     * más tarde», está hecho: eso es 200.
     */
    public static function updated(
        mixed $data = null,
        string $message = 'Recurso actualizado correctamente'
    ): JsonResponse {
        return self::success($data, $message);
    }

    /**
     * Borrado: 204 y sin cuerpo.
     *
     * Gemela de `ApiResponseTrait::deletedResponse()`. Es la excepción
     * consciente a «todas las respuestas llevan envelope»: un 204 no lleva
     * cuerpo por definición del protocolo, así que no hay dónde ponerlo.
     * Decisión tomada el 2026-09-02: se mantiene el 204 en vez de degradarlo a
     * un 200 con sobre vacío.
     */
    public static function deleted(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Colección paginada, con su bloque `meta`.
     *
     * Gemela de `ApiResponseTrait::paginatedResponse()`.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  class-string<JsonResource>|null  $resource
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        ?string $resource = null,
        string $message = 'Operación exitosa'
    ): JsonResponse {
        $elements = $resource === null
            ? $paginator->items()
            : $resource::collection($paginator->items())->resolve();

        return response()->json(
            ApiEnvelope::success($elements, $message, ['meta' => self::metaFrom($paginator)])
        );
    }

    /**
     * Añade avisos a una respuesta ya construida.
     *
     * Gemela de `ApiResponseTrait::withWarnings()`.
     *
     * @param  list<string>  $warnings
     */
    public static function withWarnings(JsonResponse $response, array $warnings): JsonResponse
    {
        if ($warnings === []) {
            return $response;
        }

        /** @var array<string,mixed> $payload */
        $payload = $response->getData(true);
        $payload['warnings'] = array_values(array_unique($warnings));

        return $response->setData($payload);
    }

    /**
     * Error genérico.
     *
     * Gemela de `ApiResponseTrait::errorResponse()`.
     *
     * El bloque `errors` admite las dos formas que la API produce de verdad:
     * el mapa `campo => [mensajes]` de la validación y la lista suelta de
     * motivos que devuelve `EnergyMonitorController` cuando ninguna lectura
     * casa con un canal dado de alta. Por eso el tipo no es `array<string,mixed>`.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function error(
        string $message = 'Error en la operación',
        int $status = 400,
        array $errors = [],
        ?Throwable $exception = null
    ): JsonResponse {
        $extra = empty($errors) ? [] : ['errors' => $errors];

        return response()->json(ApiEnvelope::error($message, $extra, $exception), $status);
    }

    /**
     * Datos de entrada inválidos (422).
     *
     * Se conserva el nombre histórico de la V1 porque es el que usan los
     * `render()` de `app/Exceptions/`.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function failed(
        string $message = 'Los datos proporcionados no son válidos.',
        array $errors = [],
        int $status = 422
    ): JsonResponse {
        return self::error($message, $status, $errors);
    }

    /** No encontrado (404). Gemela de `ApiResponseTrait::notFoundResponse()`. */
    public static function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return self::error($message, 404);
    }

    /** No autenticado (401). Gemela de `ApiResponseTrait::unauthorizedResponse()`. */
    public static function unauthorized(string $message = 'No autenticado'): JsonResponse
    {
        return self::error($message, 401);
    }

    /** Sin permiso (403). Gemela de `ApiResponseTrait::forbiddenResponse()`. */
    public static function forbidden(string $message = 'No autorizado para realizar esta acción'): JsonResponse
    {
        return self::error($message, 403);
    }

    /** Conflicto con el estado actual (409). Gemela de `ApiResponseTrait::conflictResponse()`. */
    public static function conflict(string $message = 'Conflicto con el estado actual del recurso'): JsonResponse
    {
        return self::error($message, 409);
    }

    /**
     * Error no controlado.
     *
     * No tiene gemela en el trait a propósito: un controlador no devuelve un
     * 500 a mano, lo provoca. Esto lo usa el `render()` de cierre de
     * `bootstrap/app.php`.
     *
     * El detalle de la excepción sólo viaja dentro del bloque `debug`, o sea
     * nunca en producción: el mensaje que ve quien llama es siempre el mismo.
     */
    public static function serverError(
        string $message = 'Error interno del servidor',
        ?Throwable $exception = null,
        int $status = 500
    ): JsonResponse {
        return self::error($message, $status, [], $exception);
    }

    /**
     * Bloque `meta` de un paginador.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array<string,mixed>
     */
    private static function metaFrom(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
