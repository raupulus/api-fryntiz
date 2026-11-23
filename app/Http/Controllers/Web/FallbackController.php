<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

/**
 * Controlador de respaldo para rutas web no coincidentes.
 */
class FallbackController extends Controller
{
    public function __invoke(): never
    {
        abort(404);
    }
}
