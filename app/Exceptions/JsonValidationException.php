<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Requests\Api\BaseFormRequest;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use JsonHelper;

/**
 * Datos de entrada inválidos, con el envelope de la API.
 *
 * La lanza {@see BaseFormRequest::failedValidation()},
 * o sea todos los FormRequests de la API.
 *
 * Antes tenía dos ramas: una que construía el envelope a mano para
 * `api/v2/*` y otra que llamaba a `JsonHelper` con el formato de la V1. Como
 * **todos** los FormRequests que heredan de `BaseFormRequest` viven bajo
 * `app/Http/Requests/Api/`, la segunda rama no la alcanzaba nadie. Ahora hay un
 * solo camino y lo construye `JsonHelper`, que es el mismo sitio del que sale
 * el resto de respuestas de error (revisión 2026-09-02).
 *
 * El mensaje va traducido: era una de las dos únicas cadenas de la API que
 * salían siempre en inglés.
 */
class JsonValidationException extends Exception
{
    protected $validator;

    public function __construct(Validator $validator)
    {
        parent::__construct(__('api.validation_failed'));
        $this->validator = $validator;
    }

    /**
     * Devuelve los errores de validación.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->validator->errors()->toArray();
    }

    /**
     * No se reporta: un 422 es una petición mal formada del cliente, no un
     * fallo del servidor. Llenaría el log de ruido.
     */
    public function report(): bool
    {
        return false;
    }

    public function render($request): JsonResponse
    {
        return JsonHelper::failed(__('api.validation_failed'), $this->errors());
    }
}
