<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciales de Google
|--------------------------------------------------------------------------
|
| Todo lo de Google vive aquí. Antes estaba repartido: este fichero declaraba
| `google_recaptcha_key` y `google_recaptcha_secret` leyendo `GOOGLE_CAPTCHA_KEY`
| y `GOOGLE_CAPTCHA_SECRET` —dos variables que no existen en ninguna plantilla de
| `.env` y que no leía ningún trozo de código—, mientras el reCAPTCHA que sí
| funciona se configuraba en `config/services.php` con `RECAPTCHA_SITE_KEY` y
| `RECAPTCHA_SECRET_KEY`. Dos sitios, dos juegos de variables y sólo uno vivo:
| quien rellenara las de aquí se quedaba sin protección y sin ningún aviso.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA v3
    |--------------------------------------------------------------------------
    |
    | Obligatorio en todo formulario público sin autenticación (contacto,
    | newsletter y el login de los paneles). Si no hay `secret_key`, la
    | verificación se desactiva sola: el formulario sigue funcionando sin
    | captcha. Es deliberado —permite desarrollar sin claves—, pero significa
    | que dejarlas vacías en el servidor equivale a publicar los formularios sin
    | protección, sin un solo error en los logs.
    |
    */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),

        /*
        | Puntuación mínima para dar por buena una petición.
        |
        | reCAPTCHA **v3 no dice «humano» o «bot»**: devuelve un número de 0.0 a
        | 1.0 y quien decide el corte es quien lo usa. Hasta el 2026-09-02 sólo
        | se miraba `success`, que es cierto para cualquier token bien formado y
        | sin caducar **aunque venga de un bot con 0.1** (auditoría AR-S04). O
        | sea: el captcha estaba puesto y no filtraba nada.
        |
        | Dos umbrales porque son dos riesgos distintos:
        |
        |  · Formularios públicos (contacto, newsletter): 0.5. Lo peor que pasa
        |    al cortar de más es que un mensaje no llegue, y queda registrado en
        |    el panel con su puntuación.
        |  · Login de los paneles: 0.3, más permisivo. Lo peor que pasa aquí es
        |    dejarte fuera de tu propio panel un mal día de red o con un
        |    navegador lleno de extensiones, y contra la fuerza bruta ya está el
        |    rate limit de Filament (5 intentos) y el de `api-auth`.
        |
        | Sin `secret_key` no se comprueba nada y estos números dan igual.
        */
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
        'min_score_login' => (float) env('RECAPTCHA_MIN_SCORE_LOGIN', 0.3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps / APIs
    |--------------------------------------------------------------------------
    |
    | Fuera de producción se usa la clave de desarrollo si existe, para no gastar
    | la cuota de la buena ni exponerla en un entorno local.
    |
    | La decisión se toma con `APP_ENV` y no con `app.debug`, que es lo que había
    | antes: `config('app.debug')` desde otro fichero de configuración depende de
    | que `app.php` se haya cargado ya (funciona sólo por el orden alfabético), y
    | además ataba la elección de la clave al modo de depuración, que son dos
    | cosas distintas.
    |
    */
    'api_key' => env('APP_ENV') === 'production'
        ? env('GOOGLE_API_KEY')
        : env('GOOGLE_DEV_API_KEY', env('GOOGLE_API_KEY')),

    'dev_api_key' => env('GOOGLE_DEV_API_KEY'),
];
