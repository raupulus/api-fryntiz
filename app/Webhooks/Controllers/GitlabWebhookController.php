<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;

/**
 * Class GitlabWebhookController
 *
 * Controlador para tratar los webhooks desde gitlab.
 *
 * Es necesario deshabilitar en VerifyCsrfToken cada método utilizado ya
 * que no hay sesión para validar el usuario en este tipo de peticiones.
 * Esto se lleva a cabo con la propiedad $except.
 *
 * @package App\Http\Controllers\Webhook
 */
class GitlabWebhookController extends Controller
{
    /**
     * Despliega la última versión del master para la API
     * php artisan down
     * git checkout -- .
     * git pull
     * npm install --production
     * composer install --no-interaction --no-dev --prefer-dist
     * php artisan migrate --force
     * php artisan up
     *
     */
    public function apiDeploy()
    {
        // Comprobar token.

        // Lanzar script bash.
    }

    /**
     * Procesa las notificaciones de eventos realizados en gitlab como
     * un bot, correo, procesar dato... etc...
     */
    public function apiNotification()
    {

    }
}
