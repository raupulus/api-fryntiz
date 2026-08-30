<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Conexión por defecto
    |--------------------------------------------------------------------------
    |
    | La variable se llama `BROADCAST_CONNECTION`, que es el nombre que usa
    | Laravel desde la 11. En la v1 era `BROADCAST_DRIVER` y aquí no se
    | mantiene: no hay capa de compatibilidad con la v1 en nada.
    |
    | Por defecto `null`, o sea no se emite. Hay que ponerlo a `reverb` a
    | conciencia, porque eso implica tener el demonio corriendo.
    |
    | Ver docs/info/websockets.md.
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Conexiones
    |--------------------------------------------------------------------------
    |
    | Se han quitado `pusher` y `ably`: eran las de la plantilla de Laravel, no
    | las usa nadie y tener credenciales de servicios de pago en la
    | configuración de un proyecto que no los usa sólo invita a rellenarlas.
    | Reverb habla el protocolo de Pusher, así que el cliente es el mismo.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Segundos que espera la API al empujar un evento al demonio.
                // Corto a propósito: si Reverb no responde, lo que no puede
                // pasar es que se quede colgada la subida de la estación.
                'timeout' => 5,
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('BROADCAST_REDIS_CONNECTION', 'default'),
        ],

        // Escribe el evento en el log en vez de emitirlo. Para ver qué se
        // emitiría sin levantar nada.
        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
