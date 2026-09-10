<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

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

    /** @var list<array{level: string, title: string, detail: string}> */
    private array $findings = [];

    public function handle(): int
    {
        $this->info('Comprobando la configuración de '.app()->environment().'…');
        $this->newLine();

        $this->checkAppKey();
        $this->checkDebugMode();
        $this->checkCors();
        $this->checkProxies();
        $this->checkCaptcha();
        $this->checkSession();
        $this->checkPolicies();
        $this->checkQueuesAndBroadcast();

        return $this->report();
    }

    private function checkAppKey(): void
    {
        if (blank(config('app.key'))) {
            $this->recordFailure(
                'APP_KEY vacía',
                'Sin ella no se pueden descifrar las sesiones ni las cookies. `php artisan key:generate`.'
            );
        }
    }

    private function checkDebugMode(): void
    {
        if (app()->isProduction() && config('app.debug')) {
            $this->recordFailure(
                'APP_DEBUG=true en producción',
                'Cualquier error enseña el stack trace con rutas del servidor, y el bloque `debug` '.
                'de las respuestas de la API sale con el contexto de cada petición.'
            );
        }

        if (app()->isProduction() && config('logging.default') === 'single') {
            $this->note(
                'LOG_CHANNEL=single',
                'Un único fichero que crece hasta llenar el disco. `daily` rota y conserva 14 días.'
            );
        }
    }

    private function checkCors(): void
    {
        $origins = config('cors.allowed_origins', []);

        if ($origins === []) {
            $this->recordFailure(
                'FRONTEND_URLS vacío',
                'CORS no permite ningún origen: la API responde 200 y el navegador bloquea TODAS las '.
                'respuestas. Ninguna web podrá consumirla, y no habrá ni un error en el log.'
            );

            return;
        }

        if (in_array('*', $origins, true) && config('cors.supports_credentials')) {
            $this->recordFailure(
                'FRONTEND_URLS con comodín y credenciales',
                '`*` junto a `supports_credentials` es una combinación que el navegador rechaza, '.
                'así que además de inseguro no funciona. Pon los dominios uno a uno.'
            );
        }

        foreach ($origins as $origin) {
            if (! str_starts_with((string) $origin, 'http')) {
                $this->note(
                    'Origen CORS sin esquema: '.$origin,
                    'Van URL completas («https://raupulus.dev»), no dominios sueltos. '.
                    'Sin esquema no casa con nada y el origen queda fuera en silencio.'
                );
            }
        }
    }

    private function checkProxies(): void
    {
        $proxies = array_filter(array_map('trim', explode(',', (string) config('app.trusted_proxies'))));

        if (in_array('*', $proxies, true)) {
            $this->note(
                'TRUSTED_PROXIES=*',
                'Se confía en cualquier origen para la cabecera X-Forwarded-For. Si la aplicación es '.
                'alcanzable sin pasar por el proxy, la IP se puede falsificar y con ella todos los '.
                'límites por IP: login, contacto y newsletter.'
            );
        }

        if ($proxies === []) {
            $this->note(
                'TRUSTED_PROXIES vacío',
                'Detrás de nginx o Apache, $request->ip() devuelve la IP del proxy: los límites por IP '.
                'pasan a ser un cupo global compartido por todos los visitantes.'
            );
        }
    }

    private function checkCaptcha(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (blank(config('google.recaptcha.secret_key'))) {
            $this->recordFailure(
                'RECAPTCHA_SECRET_KEY vacía en producción',
                'La verificación se desactiva sola y los formularios públicos —contacto, newsletter y '.
                'el login de los paneles— quedan sin protección, sin un solo error en los logs.'
            );

            return;
        }

        $threshold = (float) config('google.recaptcha.min_score', 0);

        if ($threshold <= 0.0) {
            $this->note(
                'RECAPTCHA_MIN_SCORE en 0',
                'reCAPTCHA v3 no dice «humano» o «bot», da una puntuación. Con el umbral a 0 pasa '.
                'cualquier token válido, también el de un bot: el captcha está puesto y no filtra nada.'
            );
        }
    }

    private function checkSession(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (! config('session.secure')) {
            $this->recordFailure(
                'SESSION_SECURE_COOKIE sin activar',
                'La cookie de sesión del panel de administración viaja sin el flag `Secure`: basta una '.
                'petición en claro para que se vea por la red.'
            );
        }

        if (blank(config('app.url')) || str_starts_with((string) config('app.url'), 'http://')) {
            $this->note(
                'APP_URL no es https',
                'De ahí salen los enlaces de los correos y las URL absolutas de la API.'
            );
        }

        // AR-FE-01. `API_URL` acaba dentro del JavaScript de las vistas —el mapa
        // de vuelos de `/airflight` compone sus endpoints con ella—. Si la
        // página va por HTTPS y la base es `http://`, el navegador bloquea esas
        // peticiones por contenido mixto: el mapa se queda vacío y en el
        // servidor no aparece ni una línea, porque la petición nunca llega a
        // salir. Es un fallo que sólo se ve abriendo la web.
        if (str_starts_with((string) config('app.api_url'), 'http://')) {
            $this->recordFailure(
                'API_URL no es https',
                'Las vistas la meten en el JavaScript del cliente. Sobre una página HTTPS el '.
                'navegador bloquea esas llamadas por contenido mixto y el mapa de vuelos se queda '.
                'vacío, sin ningún error en el servidor.'
            );
        }
    }

    /**
     * Todo modelo que el panel administra tiene que tener policy.
     *
     * Es la comprobación que habría evitado AR-SEC-01. En Filament, un recurso
     * cuyo modelo no tiene policy registrada no queda cerrado: queda **abierto**
     * —`Gate::getPolicyFor()` devuelve `null` y el recurso autoriza todas las
     * acciones—, así que un descuido al añadir un recurso nuevo no da error, da
     * acceso. Diez modelos estaban así, incluido el de los tokens de la API.
     *
     * No hace falta base de datos ni usuario: se pregunta al Gate por el mapa de
     * policies, que es información estática del arranque.
     */
    private function checkPolicies(): void
    {
        $withoutPolicy = [];

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getResources() as $resource) {
                $model = $resource::getModel();

                if (Gate::getPolicyFor($model) === null) {
                    $withoutPolicy[$model] = true;
                }
            }
        }

        if ($withoutPolicy === []) {
            return;
        }

        $this->recordFailure(
            'Recursos del panel sin policy: '.count($withoutPolicy),
            'Un modelo sin policy no está restringido, está abierto: Filament autoriza ver, crear, '.
            'editar y borrar a cualquiera que entre al panel, y al panel entra también el rol Editor. '.
            'Sin policy: '.implode(', ', array_keys($withoutPolicy))
        );
    }

    private function checkQueuesAndBroadcast(): void
    {
        if (app()->isProduction() && config('queue.default') === 'sync') {
            $this->note(
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
            $origins = collect(config('reverb.apps.apps.0.allowed_origins', []))
                ->map(static fn ($origin): string => trim((string) $origin))
                ->filter()
                ->all();

            if ($origins === [] || in_array('*', $origins, true)) {
                $this->note(
                    'REVERB_ALLOWED_ORIGINS sin acotar',
                    'Es lo único que impide que cualquier web abra un socket contra el servidor.'
                );
            }
        }
    }

    /**
     * Un fallo: algo está mal y hay que arreglarlo antes de dar por bueno el
     * despliegue. Se llama `recordFailure()` y no `fail()` ni `error()`:
     * `Command` ya tiene ambos —`fail()` lanza una excepción y `error()` pinta
     * en rojo— y esto sólo acumula el hallazgo para pintarlo al final.
     */
    private function recordFailure(string $title, string $detail): void
    {
        $this->findings[] = ['level' => 'error', 'title' => $title, 'detail' => $detail];
    }

    /**
     * Un aviso: puede ser deliberado, pero conviene mirarlo. Con `--strict`
     * también hace fallar el comando.
     */
    private function note(string $title, string $detail): void
    {
        $this->findings[] = ['level' => 'notice', 'title' => $title, 'detail' => $detail];
    }

    /**
     * Pinta el resultado y devuelve el código de salida.
     */
    private function report(): int
    {
        if ($this->findings === []) {
            $this->components->info('Sin problemas. La configuración está completa.');

            return self::SUCCESS;
        }

        $errors = 0;

        foreach ($this->findings as $finding) {
            if ($finding['level'] === 'error') {
                $errors++;
                $this->components->error($finding['title']);
            } else {
                $this->components->warn($finding['title']);
            }

            $this->line('   '.$finding['detail']);
            $this->newLine();
        }

        $notices = count($this->findings) - $errors;

        $this->line(sprintf('  <fg=red>%d error(es)</> · <fg=yellow>%d aviso(s)</>', $errors, $notices));

        if ($errors > 0) {
            return self::FAILURE;
        }

        return $this->option('strict') && $notices > 0 ? self::FAILURE : self::SUCCESS;
    }
}
