<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Requests\Api\BaseFormRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use JsonHelper;

/**
 * El `authorize()` de un FormRequest ha dicho que no.
 *
 * La lanza {@see BaseFormRequest::failedAuthorization()}.
 *
 * Igual que {@see JsonValidationException}: tenía una rama para `api/v2/*` con
 * el envelope escrito a mano y otra con el formato de la V1 que no alcanzaba
 * nadie. Ahora hay un solo camino, por `JsonHelper`, y el mensaje va traducido
 * (revisión 2026-09-02).
 */
class JsonAuthorizationException extends Exception
{
    public function __construct()
    {
        parent::__construct(__('api.forbidden'));
    }

    /*
     * Esta excepción no se registra en el log. La decisión vive en
     * `bootstrap/app.php` (`$exceptions->dontReport(...)`).
     *
     * Aquí había un `report(): bool { return false; }` que hacía **lo
     * contrario** de lo que decía su comentario: el handler de Laravel sólo
     * deja de reportar cuando `report()` devuelve algo distinto de `false`
     * (`Handler::report()`: `... && $this->container->call($reportCallable)
     * !== false`). Devolver `false` significa «repórtalo tú», así que cada
     * petición mal formada dejaba una traza de setenta líneas en producción.
     */

    public function render($request): JsonResponse
    {
        return JsonHelper::forbidden(__('api.forbidden'));
    }
}
