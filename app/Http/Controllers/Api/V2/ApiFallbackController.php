<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use JsonHelper;

/**
 * Respuesta 404 uniforme con envelope JSON para cualquier ruta API V2 inexistente.
 */
class ApiFallbackController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return JsonHelper::notFound(__('api.endpoint_not_found'));
    }
}
