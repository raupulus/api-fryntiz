<?php

declare(strict_types=1);

use App\Models\Printer;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de broadcast
|--------------------------------------------------------------------------
|
| Aquí sólo van los canales PRIVADOS y de presencia: son los que hay que
| autorizar. Un canal público no se declara porque no hay nada que decidir.
|
| El canal de las estaciones —`weather-station.{id}`— es **público a
| propósito**: esas lecturas se sirven sin autenticar por
| `GET /api/v2/weather-stations/{id}`, así que pedir un token para escucharlas
| no protegería un dato que ya es público; sólo complicaría a las ocho webs que
| consumen la API.
|
| Ver docs/info/websockets.md.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('printer.{printerId}', function (User $user, int|string $printerId) {
    $printer = Printer::with('hardwareDevice')->find($printerId);
    if (! $printer || ! $printer->hardwareDevice) {
        return false;
    }

    if ($user->isSuperAdmin() || $user->isAdmin()) {
        return true;
    }

    // La propiedad se verifica a través del hardwareDevice asociado
    if ((int) $user->id !== (int) $printer->hardwareDevice->user_id) {
        return false;
    }

    // Si es un token de dispositivo (IoT), validar que puede tocar este cacharro y tiene permiso
    if (TokenAbilities::deviceRequest($user)) {
        $token = TokenAbilities::currentToken($user);
        if ($token && ! $token->can(TokenAbilities::PRINTERS_WRITE) && ! $token->can(TokenAbilities::PRINTERS_READ)) {
            return false;
        }

        return TokenAbilities::tokenReachesDevice($user, (int) $printer->hardware_device_id);
    }

    return true;
}, ['guards' => ['sanctum', 'web']]);
