<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
use function config;

/**
 * Class Cors
 *
 * Esta clase permite el uso de CORS para las respuestas.
 *
 * @package App\Http\Middleware
 */
class Cors
{
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')){
            $response = Response::make();
        } else {
            $response = $next($request);
        }

        return $response
            //Url a la que se le dará acceso en las peticiones
            ->header("Access-Control-Allow-Origin", $request->header('origin') ?: $request->url())
            //Métodos que a los que se da acceso
            ->header("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
            //Headers de la petición
            ->header("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, X-Token-Auth, Authorization");
    }
}
