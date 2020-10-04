<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
use function config;

class CorsAllowAll
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')){
            $response = Response::make();
        } else {
            $response = $next($request);
        }

        return $response
            //Url a la que se le dará acceso en las peticiones
            ->header("Access-Control-Allow-Origin", '*')
            //Métodos que a los que se da acceso
            ->header("Access-Control-Allow-Methods", 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            //Headers de la petición
            ->header("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, X-Token-Auth, Authorization");
    }
}
