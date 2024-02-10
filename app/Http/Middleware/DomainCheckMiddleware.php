<?php

namespace App\Http\Middleware;

use App\Models\Platform;
use Closure;
use Illuminate\Http\Request;

class DomainCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $allowedHosts = array_merge(Platform::getAllDomains(), config('sanctum.stateful'));
        $requestHost = parse_url($request->headers->get('Origin'), PHP_URL_HOST) ?? $request->headers->get('Origin');

        if (!in_array($requestHost, $allowedHosts, false) && !app()->runningUnitTests()) {
            /*
            $requestInfo = [
                'host' => $requestHost,
                'ip' => $request->getClientIp(),
                'url' => $request->getRequestUri(),
                'agent' => $request->header('User-Agent'),
            ];
            */

            //event(new UnauthorizedAccess($requestInfo));

            //throw new SuspiciousOperationException('This host is not allowed');



            \Log::debug([$request->headers, $requestHost]);

            /*
            return \JsonHelper::success(['data' => [
                'headers' => $request->headers->all(),
                'requestHost' => $requestHost,
                'all' => $request->all(),
            ]]);
            */


            if ($request->isJson()) {
                return \JsonHelper::forbidden('¿Dónde ibas?');
            }

            abort(403, '¿Dónde ibas?');
        }

        \Log::debug([$request->headers, $requestHost]);


        return $next($request);
    }
}
