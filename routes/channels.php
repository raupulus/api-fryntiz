<?php

declare(strict_types=1);

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
