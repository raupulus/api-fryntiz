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


        $ipCount = Cache::remember('ipCount_' . $ipSlug, 60, function () use ($ip) {
            return 1;
        });


        if (!config('app.debug') && $ipCount > 5) {
            return Response::make('Too many requests', 429);
        }

        Cache::put('ipCount_' . $ipSlug, $ipCount + 1, 300);

        return $next($request);
    }
}
