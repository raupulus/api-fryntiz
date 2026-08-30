<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuestas JSON estandarizadas de la API V2.
 *
 * El envelope `{success, message, data}` se conserva (D101): ya estaba
 * implementado y cambiarlo obligaría a tocar las ocho webs.
 */
trait ApiResponseTrait
{
    /**
     * Respuesta exitosa con datos.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operación exitosa',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Respuesta exitosa para recurso creado (201).
     *
     * Si se indica `$location`, se añade la cabecera `Location` con la URL del
     * recurso recién creado, que es lo que dice el contrato (§4).
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
     * @param  list<string>  $warnings
     */
    protected function withWarnings(JsonResponse $jsonResponse, array $warnings): JsonResponse
    {
        if ($warnings === []) {
            return $jsonResponse;
        }

        /** @var array<string,mixed> $payload */
        $payload = $jsonResponse->getData(true);
        $payload['warnings'] = array_values(array_unique($warnings));

        return $jsonResponse->setData($payload);
    }

    /**
     * Respuesta 204 sin contenido, para borrados.
     *
     * Un 204 no lleva cuerpo por definición, así que aquí no hay envelope.
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
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  class-string<JsonResource>|null  $resource  Resource con el que envolver cada elemento.
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        ?string $resource = null,
        string $message = 'Operación exitosa'
    ): JsonResponse {
        $elements = $resource === null
            ? $paginator->items()
            : $resource::collection($paginator->items())->resolve();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $elements,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Respuesta de error.
     */
    protected function errorResponse(
        string $message = 'Error en la operación',
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta 404 no encontrado.
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta 401 no autenticado.
     */
    protected function unauthorizedResponse(string $message = 'No autenticado'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Respuesta 403 no autorizado.
     */
    protected function forbiddenResponse(string $message = 'No autorizado'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Respuesta 409 conflicto.
     */
    protected function conflictResponse(string $message = 'Conflicto con el estado actual del recurso'): JsonResponse
    {
        return $this->errorResponse($message, 409);
    }
}
