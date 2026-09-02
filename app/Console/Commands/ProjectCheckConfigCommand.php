<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Comprueba que la configuración desplegada no tiene fallos silenciosos.
 *
 * Existe por lo que la auditoría de 2026-09-02 llamó AR-D03: hay ajustes que,
 * mal puestos, **no producen ningún error**. La aplicación arranca, responde
 * 200, no escribe nada en el log, y algo no funciona:
 *
 *  · `FRONTEND_URLS` vacío → la API responde perfectamente y el navegador
 *    bloquea todas las respuestas. Desde el servidor parece que funciona;
 *    desde las ocho webs no funciona nada. Es el más caro de diagnosticar.
 *  · `RECAPTCHA_SECRET_KEY` vacío → los formularios públicos salen sin
 *    protección, y es deliberado que sea así en desarrollo. `config/google.php`
 *    ya avisa en un comentario de que dejarlo vacío en el servidor «equivale a
 *    publicar los formularios sin protección, sin un solo error en los logs».
 *  · `TRUSTED_PROXIES` mal puesto → todos los límites por IP pasan a ser un
 *    cupo global compartido por todos los visitantes.
 *
 * Se ejecuta a mano después de desplegar, o desde el propio script de
 * despliegue. Devuelve código 1 si hay algún fallo, para que un `&&` corte.
 */
class ProjectCheckConfigCommand extends Command
{
    protected $signature = 'project:check-config
        {--strict : Trata también los avisos como error}';

    protected $description = 'Comprueba la configuración desplegada y avisa de los fallos que no dan error por sí solos';

    /** @var list<array{nivel: string, titulo: string, detalle: string}> */
    private array $hallazgos = [];

    public function handle(): int
    {
        $this->info('Comprobando la configuración de '.app()->environment().'…');
        $this->newLine();

        $this->comprobarClaveDeAplicacion();
        $this->comprobarDepuracion();
        $this->comprobarCors();
        $this->comprobarProxies();
        $this->comprobarCaptcha();
        $this->comprobarSesion();
        $this->comprobarColasYBroadcast();

        return $this->informar();
    }

    private function comprobarClaveDeAplicacion(): void
    {
        if (blank(config('app.key'))) {
            $this->fallo(
                'APP_KEY vacía',
                'Sin ella no se pueden descifrar las sesiones ni las cookies. `php artisan key:generate`.'
            );
        }
    }

    private function comprobarDepuracion(): void
    {
        if (app()->isProduction() && config('app.debug')) {
            $this->fallo(
                'APP_DEBUG=true en producción',
                'Cualquier error enseña el stack trace con rutas del servidor, y el bloque `debug` '.
                'de las respuestas de la API sale con el contexto de cada petición.'
            );
        }

        if (app()->isProduction() && config('logging.default') === 'single') {
            $this->apunte(
                'LOG_CHANNEL=single',
                'Un único fichero que crece hasta llenar el disco. `daily` rota y conserva 14 días.'
            );
        }
    }

    private function comprobarCors(): void
    {
        $origenes = config('cors.allowed_origins', []);

        if ($origenes === []) {
            $this->fallo(
                'FRONTEND_URLS vacío',
                'CORS no permite ningún origen: la API responde 200 y el navegador bloquea TODAS las '.
                'respuestas. Ninguna web podrá consumirla, y no habrá ni un error en el log.'
            );

            return;
        }

        if (in_array('*', $origenes, true) && config('cors.supports_credentials')) {
            $this->fallo(
                'FRONTEND_URLS con comodín y credenciales',
                '`*` junto a `supports_credentials` es una combinación que el navegador rechaza, '.
                'así que además de inseguro no funciona. Pon los dominios uno a uno.'
            );
        }

        foreach ($origenes as $origen) {
            if (! str_starts_with((string) $origen, 'http')) {
                $this->apunte(
                    'Origen CORS sin esquema: '.$origen,
                    'Van URL completas («https://raupulus.dev»), no dominios sueltos. '.
                    'Sin esquema no casa con nada y el origen queda fuera en silencio.'
                );
            }
        }
    }

    private function comprobarProxies(): void
    {
        $proxies = array_filter(array_map('trim', explode(',', (string) config('app.trusted_proxies'))));

        if (in_array('*', $proxies, true)) {
            $this->apunte(
                'TRUSTED_PROXIES=*',
                'Se confía en cualquier origen para la cabecera X-Forwarded-For. Si la aplicación es '.
                'alcanzable sin pasar por el proxy, la IP se puede falsificar y con ella todos los '.
                'límites por IP: login, contacto y newsletter.'
            );
        }

        if ($proxies === []) {
            $this->apunte(
                'TRUSTED_PROXIES vacío',
                'Detrás de nginx o Apache, $request->ip() devuelve la IP del proxy: los límites por IP '.
                'pasan a ser un cupo global compartido por todos los visitantes.'
            );
        }
    }

    private function comprobarCaptcha(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (blank(config('google.recaptcha.secret_key'))) {
            $this->fallo(
                'RECAPTCHA_SECRET_KEY vacía en producción',
                'La verificación se desactiva sola y los formularios públicos —contacto, newsletter y '.
                'el login de los paneles— quedan sin protección, sin un solo error en los logs.'
            );

            return;
        }

        $umbral = (float) config('google.recaptcha.min_score', 0);

        if ($umbral <= 0.0) {
            $this->apunte(
                'RECAPTCHA_MIN_SCORE en 0',
                'reCAPTCHA v3 no dice «humano» o «bot», da una puntuación. Con el umbral a 0 pasa '.
                'cualquier token válido, también el de un bot: el captcha está puesto y no filtra nada.'
            );
        }
    }

    private function comprobarSesion(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (! config('session.secure')) {
            $this->fallo(
                'SESSION_SECURE_COOKIE sin activar',
                'La cookie de sesión del panel de administración viaja sin el flag `Secure`: basta una '.
                'petición en claro para que se vea por la red.'
            );
        }

        if (blank(config('app.url')) || str_starts_with((string) config('app.url'), 'http://')) {
            $this->apunte(
                'APP_URL no es https',
                'De ahí salen los enlaces de los correos y las URL absolutas de la API.'
            );
        }
    }

    private function comprobarColasYBroadcast(): void
    {
        if (app()->isProduction() && config('queue.default') === 'sync') {
            $this->apunte(
                'QUEUE_CONNECTION=sync en producción',
                'Cada job corre DENTRO de la petición: el visitante espera al contador de visitas y al '.
                'servidor de correo, y si el SMTP está caído se lleva el error.'
            );
        }

        if (config('broadcasting.default') === 'reverb') {
            // De `config`, no de `env()`: en el servidor el despliegue hace
            // `config:cache` y a partir de ahí Laravel no carga el `.env`, así
            // que `env()` devuelve null y este comando avisaría de un problema
            // inventado. Es el mismo despiste que tuvo `TRUSTED_PROXIES`.
            $origenes = collect(config('reverb.apps.apps.0.allowed_origins', []))
                ->map(static fn ($origen): string => trim((string) $origen))
                ->filter()
                ->all();

            if ($origenes === [] || in_array('*', $origenes, true)) {
                $this->apunte(
                    'REVERB_ALLOWED_ORIGINS sin acotar',
                    'Es lo único que impide que cualquier web abra un socket contra el servidor.'
                );
            }
        }
    }

    /**
     * Un fallo: algo está mal y hay que arreglarlo antes de dar por bueno el
     * despliegue. Se llama `fallo()` y no `error()` porque `Command::error()`
     * ya existe y es lo que pinta en rojo.
     */
    private function fallo(string $titulo, string $detalle): void
    {
        $this->hallazgos[] = ['nivel' => 'error', 'titulo' => $titulo, 'detalle' => $detalle];
    }

    /**
     * Un aviso: puede ser deliberado, pero conviene mirarlo. Con `--strict`
     * también hace fallar el comando.
     */
    private function apunte(string $titulo, string $detalle): void
    {
        $this->hallazgos[] = ['nivel' => 'aviso', 'titulo' => $titulo, 'detalle' => $detalle];
    }

    /**
     * Pinta el resultado y devuelve el código de salida.
     */
    private function informar(): int
    {
        if ($this->hallazgos === []) {
            $this->components->info('Sin problemas. La configuración está completa.');

            return self::SUCCESS;
        }

        $errores = 0;

        foreach ($this->hallazgos as $hallazgo) {
            if ($hallazgo['nivel'] === 'error') {
                $errores++;
                $this->components->error($hallazgo['titulo']);
            } else {
                $this->components->warn($hallazgo['titulo']);
            }

            $this->line('   '.$hallazgo['detalle']);
            $this->newLine();
        }

        $avisos = count($this->hallazgos) - $errores;

        $this->line(sprintf('  <fg=red>%d error(es)</> · <fg=yellow>%d aviso(s)</>', $errores, $avisos));

        if ($errores > 0) {
            return self::FAILURE;
        }

        return $this->option('strict') && $avisos > 0 ? self::FAILURE : self::SUCCESS;
    }
}
