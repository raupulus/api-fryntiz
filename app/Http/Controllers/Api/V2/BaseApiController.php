<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

/**
 * Controlador base para todos los endpoints de la API V2.
 * Provee métodos estandarizados de respuesta JSON.
 */
abstract class BaseApiController extends Controller
{
    use ApiResponseTrait;
}
