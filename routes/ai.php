<?php

declare(strict_types=1);

use App\Mcp\Servers\ApiRaupulusServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| Servidor MCP — SOLO fuera de producción
|--------------------------------------------------------------------------
|
| `Mcp::web()` publica el servidor como tres rutas HTTP (GET/POST/DELETE
| /mcp/api-raupulus) SIN autenticación. Sus herramientas leen el esquema de la
| base de datos, el estado del sistema y —`RunSpecificTestTool`— lanzan un
| `Process::run()` con `php artisan test`. En un servidor público eso es una
| puerta abierta, y el MCP no le hace falta a nadie en producción: es una
| herramienta de desarrollo para el editor.
|
| La condición se evalúa al registrar las rutas, así que con `route:cache`
| hecho en el servidor (APP_ENV=production) las rutas ni siquiera entran en el
| fichero de caché.
|
*/

if (app()->environment('production')) {
    return;
}

Mcp::web('/mcp/api-raupulus', ApiRaupulusServer::class);
Mcp::local('api-raupulus', ApiRaupulusServer::class);
