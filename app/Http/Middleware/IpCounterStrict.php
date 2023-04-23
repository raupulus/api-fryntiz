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
 * @package App\Http\Middleware
 */
class IpCounter
{
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        $ipSlug = Str::slug($ip, '_');


        $ipCount = Cache::remember('ipCount_' . $ipSlug, 300, function () use ($ip) {
            return 1;
        });


        if ($ipCount > 5) {
            return Response::make('Too many requests', 429);
        }

        $ipCount = Cache::put('ipCount_' . $ipSlug, $ipCount + 1, 300);


        dd($ipCount);




        $response = $next($request);
    }
}
