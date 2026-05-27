<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait para estandarizar respuestas JSON de la API V2.
 */
trait ApiResponseTrait
{
    /**
     * Respuesta exitosa con datos.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operacion exitosa',
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
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Recurso creado correctamente'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Respuesta de error.
     */
    protected function errorResponse(
        string $message = 'Error en la operacion',
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
}
