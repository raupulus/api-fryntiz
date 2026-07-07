<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Estación principal por defecto
    |--------------------------------------------------------------------------
    |
    | Identificador del HardwareDevice que se usará como estación "principal"
    | cuando no se indique una estación concreta (por ejemplo en el widget
    | resumen embebido). Si se deja en null, el sistema resuelve automáticamente
    | la primera estación de exterior disponible y, en su defecto, cualquier
    | estación. Puede sobreescribirse por dispositivo desde el frontend.
    |
    */

    'main_station_id' => env('WEATHER_STATION_MAIN_ID'),

];
