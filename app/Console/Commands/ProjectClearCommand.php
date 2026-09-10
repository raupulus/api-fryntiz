<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class ProjectClearCommand extends Command
{
    protected $signature = 'project:clear
        {--key : Regenerar la APP_KEY también en producción}
        {--no-key : No regenerar la APP_KEY}
        {--production : Recachear al terminar aunque el entorno no sea producción}
        {--force : No preguntar nada}';

    /** @var array<string> */
    protected $aliases = ['xerintel:clear'];

    protected $description = 'Limpia las cachés del proyecto; en producción además recachea, respeta la APP_KEY y no vacía las colas';

    /**
     * `php artisan project:clear`, sin nada más, tiene que ser lo correcto en
     * los dos lados. Ése era el objetivo del comando y se había perdido: en el
     * servidor había que acordarse de `--production --no-key --force`, y
     * olvidarse de `--no-key` regeneraba la APP_KEY de producción.
     *
     * Ahora el comando mira `APP_ENV` y decide él:
     *
     * | | Desarrollo | Producción |
     * |-|-|-|
     * | APP_KEY | se regenera | se conserva (hace falta `--key`) |
     * | Colas | `queue:clear` | `queue:restart` |
     * | Recacheo | no | sí |
     *
     * Los flags siguen ahí para forzar cualquiera de las dos columnas desde el
     * otro lado, pero ya no hay que recordarlos para el uso normal.
     */
    public function handle(): int
    {
        // Se resuelve una vez y se pasa a las decisiones. `config:clear` borra
        // el fichero de caché, no la configuración ya cargada en memoria, así
        // que este valor es el mismo antes y después de limpiar.
        $inProduction = app()->environment('production');

        $this->info('Iniciando limpieza completa del proyecto...');

        if ($inProduction) {
            $this->line('▶ Entorno «production»: se conserva la APP_KEY, no se vacían las colas y se recachea al terminar.');
        }

        // 1. Limpieza de cachés de la aplicación
        // Antes de tocar nada: ¿la clave que está usando la aplicación es la
        // misma que la del `.env`?
        //
        // Con `config:cache`, la APP_KEY que usa la aplicación es la que quedó
        // CONGELADA en `bootstrap/cache/config.php`, no la del `.env`. Si en
        // algún momento se regeneró la clave y no se volvió a cachear, las dos
        // dejan de coincidir y el sitio sigue funcionando con la cacheada sin
        // que se note nada.
        //
        // El día que alguien limpia la caché, la aplicación pasa a leer la del
        // `.env` y todo lo cifrado con la otra —sesiones, cookies, los
        // `two_factor_secret`— deja de descifrarse de golpe. Parece que la
        // limpieza «ha roto la clave», y lo que ha hecho es destapar que ya
        // estaban desalineadas.
        $this->warnIfKeyMismatch();

        // Antes de nada: los directorios de trabajo. `composer dump-autoload`
        // dispara `package:discover`, que escribe en `bootstrap/cache`, y
        // `view:cache` necesita `storage/framework/views`. Si faltan, el
        // despliegue se queda con las cachés borradas y ninguna rehecha.
        $this->ensureWorkingDirectories();

        $this->line('▶ Limpiando cachés de configuración, rutas, vistas, eventos y optimizaciones...');
        $this->call('optimize:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('event:clear');
        $this->call('clear-compiled');

        // Limpiar debugbar si está registrado
        try {
            if ($this->getApplication()->has('debugbar:clear')) {
                $this->call('debugbar:clear');
            }
        } catch (\Throwable) {
            // Ignorar si no está instalado o disponible
        }

        // 2. Colas
        //
        // En producción NO se vacían. La conexión por defecto es `database`, o
        // sea que `queue:clear` borra la tabla `jobs`: correos sin enviar, PDFs
        // de currículum sin generar, lo que hubiera esperando. Un despliegue no
        // tira trabajo pendiente.
        //
        // Lo que sí hace falta ahí es lo contrario: un worker lleva el código
        // cargado en memoria desde que arrancó y seguiría ejecutando el viejo
        // después de desplegar. `queue:restart` le dice que termine el job que
        // tenga entre manos y salga, para que el supervisor lo levante con el
        // código nuevo.
        if ($inProduction) {
            $this->line('▶ Avisando a los workers para que recojan el código nuevo (queue:restart)...');
            $this->call('queue:restart');
        } else {
            $this->line('▶ Limpiando colas de trabajo (queue:clear)...');
            try {
                $this->call('queue:clear', ['--force' => true]);
            } catch (\Throwable $e) {
                $this->warn('Aviso al limpiar colas: '.$e->getMessage());
            }
        }

        // 3. Regeneración de clave segura en .env
        if ($this->shouldRegenerateKey($inProduction)) {
            // Lo que no se ve venir es el 2FA: Fortify guarda
            // `two_factor_secret` CIFRADO con la APP_KEY, así que quien lo tenga
            // activo se queda sin poder completar el segundo factor y hay que
            // volver a darlo de alta. Los tokens de Sanctum no se ven afectados
            // porque se guardan hasheados, no cifrados.
            $twoFactorUsers = $this->count2faUsers();

            if ($twoFactorUsers > 0) {
                $this->warn(
                    "Aviso: {$twoFactorUsers} usuario(s) tienen el doble factor activo. Al cambiar la APP_KEY "
                    .'su `two_factor_secret` deja de poder descifrarse y tendrán que volver a configurarlo.'
                );
            }

            $regenerar = true;

            if ($inProduction && ! $this->option('force')) {
                $regenerar = $this->confirm(
                    'Vas a regenerar APP_KEY en producción: esto invalida sesiones, tokens y cualquier '
                    .'dato cifrado con la clave actual. ¿Deseas continuar?'
                );
            }

            // Decir «no» salta la clave y sigue; no aborta el comando.
            //
            // Antes hacía `return self::FAILURE` aquí, y eso dejaba el peor de
            // los estados posibles: las cachés ya se habían limpiado en el paso
            // 1, así que la aplicación se quedaba SIN caché de configuración ni
            // de rutas y encima sin recachear, porque el recacheo va al final.
            // O sea, quien contestaba «no» para no romper nada acababa con el
            // sitio a medio limpiar.
            if ($regenerar) {
                $this->line('▶ Regenerando clave de aplicación (APP_KEY)...');
                $this->call('key:generate', ['--force' => true]);
            } else {
                $this->warn('Se conserva la APP_KEY actual. El resto de la limpieza continúa.');
            }
        } else {
            $this->line('▶ Se conserva la APP_KEY actual.');
        }

        // 4. Recomponer autoload de Composer
        $this->line('▶ Recomponiendo autoload de Composer (composer dump-autoload)...');
        $composerHome = sys_get_temp_dir().'/.composer';
        if (! is_dir($composerHome)) {
            @mkdir($composerHome, 0755, true);
        }

        $env = array_merge($_ENV, $_SERVER, [
            'COMPOSER_HOME' => $composerHome,
        ]);

        $process = Process::fromShellCommandline('composer dump-autoload', base_path(), $env);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('✓ Autoload de Composer recompuesto con éxito.');
        } else {
            $this->warn('Aviso al recomponer autoload: '.trim($process->getErrorOutput()));
        }

        // 5. Recacheo: siempre en producción, y bajo petición fuera de ella.
        if ($inProduction || $this->option('production')) {
            $this->newLine();
            $this->info('Recacheando optimizaciones para producción...');
            $this->call('config:cache');
            $this->call('route:cache');
            $this->call('view:cache');
            $this->call('event:cache');
        }

        $this->newLine();
        $this->info('✅ El proyecto ha quedado limpio y preparado.');

        return self::SUCCESS;
    }

    /**
     * Crea los directorios que Laravel necesita para cachear.
     *
     * `view:cache` aborta con «Please provide a valid cache path» si no existe
     * `storage/framework/views`, y con él se queda el despliegue a medias:
     * cachés borradas y ninguna rehecha.
     *
     * No están en git —su contenido es basura generada— y los `.gitignore` que
     * los mantienen vivos son justo lo que un despliegue puede no traer. Crearlos
     * aquí es más barato que descubrirlo con el sitio caído.
     */
    private function ensureWorkingDirectories(): void
    {
        $directories = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $created = [];

        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                continue;
            }

            if (@mkdir($directory, 0775, true) || is_dir($directory)) {
                $created[] = str_replace(base_path().'/', '', $directory);

                continue;
            }

            // Sin poder crearlo, el recacheo va a fallar igual: mejor decirlo
            // ahora y con el nombre del directorio que con «cache path».
            $this->error("No se ha podido crear «{$directory}». Compruébalo a mano antes de seguir.");
        }

        if ($created !== []) {
            $this->line('▶ Directorios de trabajo que faltaban: '.implode(', ', $created));
        }
    }

    /**
     * ¿Se regenera la APP_KEY?
     *
     * ⚠️ FUERA DE PRODUCCIÓN, REGENERAR POR DEFECTO ES INTENCIONADO. NO
     * INVERTIRLO. Cada auditoría propone lo mismo: que la clave sólo se
     * regenere con un flag explícito, porque «el comportamiento por defecto es
     * destructivo». Y sí lo es: ése es justamente el propósito del comando en
     * desarrollo, dejar el proyecto como recién instalado; conservar la clave
     * sería hacer media limpieza. Ver docs/info/decisiones-tecnicas.md D15
     * antes de volver a proponerlo.
     *
     * En producción es al revés y no por prudencia genérica: allí el comando se
     * ejecuta después de cada despliegue, y regenerar la clave en ese momento
     * invalida las sesiones abiertas y deja sin descifrar los
     * `two_factor_secret`. Hace falta pedirlo a propósito con `--key`, que es
     * lo suyo para una rotación de clave, que no tiene nada que ver con subir
     * código.
     */
    private function shouldRegenerateKey(bool $inProduction): bool
    {
        if ($this->option('no-key')) {
            return false;
        }

        return $inProduction ? (bool) $this->option('key') : true;
    }

    /**
     * Cuántas cuentas tienen el doble factor configurado.
     *
     * Consulta directa para no depender del modelo ni de sus casts, y tolerante
     * a fallos: es un aviso, no puede tumbar la limpieza (por ejemplo, si se
     * ejecuta sin base de datos disponible).
     */
    private function count2faUsers(): int
    {
        try {
            return DB::table('users')->whereNotNull('two_factor_secret')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Compara la APP_KEY congelada en la caché de configuración con la del
     * `.env`, y avisa si no son la misma.
     *
     * No aborta ni arregla nada: sólo lo dice antes de limpiar, que es cuando
     * todavía se puede decidir. Ver el comentario de `handle()`.
     */
    private function warnIfKeyMismatch(): void
    {
        $cache = base_path('bootstrap/cache/config.php');

        if (! is_file($cache)) {
            return;
        }

        try {
            $cached = (require $cache)['app']['key'] ?? null;
        } catch (\Throwable) {
            return;
        }

        // Se lee el fichero, no `env()` ni `config()`.
        //
        // `config('app.key')` devolvería justo la cacheada, que es el otro lado
        // de la comparación. Y `env()` tampoco vale: con la configuración
        // cacheada Laravel se salta la carga del `.env` y devuelve null. Lo
        // único que dice la verdad aquí es el fichero.
        $fromEnv = $this->keyFromEnvFile();

        if (! is_string($cached) || ! is_string($fromEnv) || $cached === $fromEnv) {
            return;
        }

        $this->warn('⚠ La APP_KEY de la caché de configuración NO es la del .env.');
        $this->line('  La aplicación está funcionando con la cacheada. Al limpiar pasará a usar la');
        $this->line('  del .env, y lo cifrado con la otra —sesiones, cookies, doble factor— dejará');
        $this->line('  de descifrarse. Si lo que quieres es conservar la que funciona ahora, cópiala');
        $this->line('  al .env antes de seguir:');
        $this->newLine();
        $this->line('    '.$cached);
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('¿Seguir de todas formas?', true)) {
            $this->warn('Limpieza cancelada. No se ha tocado nada.');
            exit(self::FAILURE);
        }
    }

    /**
     * La APP_KEY tal y como está escrita en el `.env`, sin pasar por la
     * configuración de Laravel.
     */
    private function keyFromEnvFile(): ?string
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);

        if (preg_match('/^APP_KEY\s*=\s*"?([^"\r\n]*)"?/m', $contents, $matches) !== 1) {
            return null;
        }

        $key = trim($matches[1]);

        return $key === '' ? null : $key;
    }
}
