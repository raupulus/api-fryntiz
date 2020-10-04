<?php

namespace App\Http\Middleware;

use Closure;
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
        return $next($request)
            //Url a la que se le dará acceso en las peticiones
            ->header("Access-Control-Allow-Origin", config('app.url'))
            //Métodos que a los que se da acceso
            ->header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE")
            //Headers de la petición
            ->header("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, X-Token-Auth, Authorization");
    }
}
