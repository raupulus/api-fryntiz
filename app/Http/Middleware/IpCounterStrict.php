<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

/**
 * Class IpCounter
 *
 * Cuenta las repeticiones de ip en un periodo de tiempo.
 *
 * Limita a 5 peticiones de contacto por ip en un minuto
 *
 * @package App\Http\Middleware
 */
class IpCounterStrict
{
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        $ipSlug = Str::slug($ip, '_');


        $ipCount = Cache::remember('ipCount_' . $ipSlug, 60, function () {
            return 0;
        });

        Cache::put('ipCount_' . $ipSlug, $ipCount + 1, 60);

        if ($ipCount > 10) {
            // TODO: Si pasa de 10 peticiones en un minuto, bloquear la ip durante 1 hora? reportar? crear panel
            // de seguimiento de ip's/actividad sospechosas?.
            // Guardar todo lo que venga de la request para poder analizarlo.
        }

        if (!config('app.debug') && $ipCount > 5) {
            if ($request->isJson()) {
                return \response()->json([
                    'messages' => [
                        'errors' => [
                            'Demasiadas peticiones desde tu ip, por favor, espera un minuto antes de volver a intentarlo.'
                        ]
                    ],
                ], 429);
            }

            return Response::make('Too many requests', 429);
        }

        return $next($request);
    }
}
