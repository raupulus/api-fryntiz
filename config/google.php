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
