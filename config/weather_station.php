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

    /*
    |--------------------------------------------------------------------------
    | Zona principal del widget
    |--------------------------------------------------------------------------
    |
    | El widget de portada va por ZONA, no por estación. Fijarlo a una estación
    | tenía un efecto que se veía en cuanto esa dejaba de subir: seguía
    | enseñando su último valor mientras la de al lado, en la misma azotea,
    | subía el dato bueno. Yendo por zona, de cada magnitud se coge el registro
    | más reciente de cualquiera de sus estaciones.
    |
    | En null se resuelve sola: la primera zona de exterior que haya.
    |
    */

    'main_zone' => env('WEATHER_STATION_MAIN_ZONE'),

    /*
    |--------------------------------------------------------------------------
    | Ventana de la cuenta de rayos
    |--------------------------------------------------------------------------
    |
    | v1 contaba los de los últimos 10 minutos y v2 los de las últimas 6 horas.
    | Son dos cosas distintas y las dos tienen sentido, así que el valor por
    | defecto es una hora y se puede pedir otra cosa con `?minutes=` (C3):
    |
    |   GET /weather-stations/3/lightnings?minutes=10
    |   GET /weather-stations/3/lightnings?minutes=360
    |
    */

    'lightning_window_minutes' => (int) env('WEATHER_LIGHTNING_WINDOW_MINUTES', 60),

    'lightning_window_minutes_max' => 10080,   // una semana

];
