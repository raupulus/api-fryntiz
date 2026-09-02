<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\Http\ApiEnvelope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonHelper;

/**
 * Respuestas JSON estandarizadas de la API V2, para los controladores.
 *
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │ ⚠️  ESTE TRAIT Y {@see JsonHelper} SON HERMANOS.                         │
 * │                                                                           │
 * │ Devuelven EXACTAMENTE lo mismo y se mantienen los dos a propósito:        │
 * │                                                                           │
 * │  · Este **trait** es lo que usan los controladores de la API.             │
 * │  · El **helper** es estático y llega donde un trait no puede: los         │
 * │    handlers de excepciones de `bootstrap/app.php`, la ruta de cierre de   │
 * │    `routes/api/v2.php` y los `render()` de `app/Exceptions/`.             │
 * │                                                                           │
 * │ 👉 SI TOCAS UN MÉTODO DE AQUÍ, TOCA SU GEMELO EN `JsonHelper`.            │
 * │                                                                           │
 * │ La forma del sobre no está escrita en ninguno de los dos: vive en         │
 * │ {@see ApiEnvelope}. Y `tests/Feature/Api/V2/ApiResponseParityTest.php`    │
 * │ compara las dos salidas método a método.                                  │
 * └───────────────────────────────────────────────────────────────────────────┘
 *
 * El envelope `{success, message, data}` se conserva (D101): ya estaba
 * implementado y cambiarlo obligaría a tocar las ocho webs.
 */
trait ApiResponseTrait
{
    /**
     * Respuesta exitosa con datos.
     *
     * Gemela de `JsonHelper::success()`.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operación exitosa',
        int $status = 200
    ): JsonResponse {
        return response()->json(ApiEnvelope::success($data, $message), $status);
    }

    /**
     * Respuesta exitosa para recurso creado (201).
     *
     * Si se indica `$location`, se añade la cabecera `Location` con la URL del
     * recurso recién creado, que es lo que dice el contrato (§4).
     *
     * Gemela de `JsonHelper::created()`.
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Recurso creado correctamente',
        ?string $location = null
    ): JsonResponse {
        $jsonResponse = $this->successResponse($data, $message, 201);

        return $location === null ? $jsonResponse : $jsonResponse->header('Location', $location);
    }

    /**
     * Añade avisos a una respuesta ya construida.
     *
     * Un aviso **no es un error**: la petición se ha guardado. Es «esto ha
     * entrado, pero mira esto otro»: una corriente negativa, un elemento sin
     * tensión nominal, un canal que no está dado de alta. Sin esto, un montaje
     * mal configurado responde 201 durante meses y nadie se entera (D115).
     *
     * Gemela de `JsonHelper::withWarnings()`.
     *
     * @param  list<string>  $warnings
     */
    protected function withWarnings(JsonResponse $jsonResponse, array $warnings): JsonResponse
    {
        return JsonHelper::withWarnings($jsonResponse, $warnings);
    }

    /**
     * Respuesta 204 sin contenido, para borrados.
     *
     * Un 204 no lleva cuerpo por definición, así que aquí no hay envelope. Es
     * la única excepción consciente a «todas las respuestas llevan sobre», y se
     * mantiene: decisión del 2026-09-02.
     *
     * Gemela de `JsonHelper::deleted()`.
     */
    protected function deletedResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Colección paginada.
     *
     * Todas las colecciones de la API van paginadas (§4). El bloque `meta` sale
     * del paginador y no se construye a mano en cada controlador.
     *
     * Gemela de `JsonHelper::paginated()`.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  class-string<JsonResource>|null  $resource  Resource con el que envolver cada elemento.
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        ?string $resource = null,
        string $message = 'Operación exitosa'
    ): JsonResponse {
        return JsonHelper::paginated($paginator, $resource, $message);
    }

    /**
     * Respuesta de error.
     *
     * Gemela de `JsonHelper::error()`.
     *
     * El bloque `errors` admite las dos formas que la API produce de verdad:
     * el mapa `campo => [mensajes]` de la validación y la lista suelta de
     * motivos que devuelve `EnergyMonitorController` cuando ninguna lectura
     * casa con un canal dado de alta. Por eso el tipo no es `array<string,mixed>`.
     *
     * @param  array<array-key, mixed>  $errors
     */
    protected function errorResponse(
        string $message = 'Error en la operación',
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        return JsonHelper::error($message, $status, $errors);
    }

    /**
     * Respuesta 404 no encontrado.
     *
     * Gemela de `JsonHelper::notFound()`.
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta 401 no autenticado.
     *
     * Gemela de `JsonHelper::unauthorized()`.
     */
    protected function unauthorizedResponse(string $message = 'No autenticado'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Respuesta 403 sin permiso.
     *
     * Gemela de `JsonHelper::forbidden()`.
     */
    protected function forbiddenResponse(string $message = 'No autorizado para realizar esta acción'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Respuesta 409 conflicto con el estado actual del recurso.
     *
     * Gemela de `JsonHelper::conflict()`.
     */
    protected function conflictResponse(string $message = 'Conflicto con el estado actual del recurso'): JsonResponse
    {
        return $this->errorResponse($message, 409);
    }
}
