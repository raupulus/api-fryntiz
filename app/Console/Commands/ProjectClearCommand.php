<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class ProjectClearCommand extends Command
{
    protected $signature = 'project:clear
        {--production : Recachear después de limpiar}
        {--no-key : No regenerar la clave de aplicación APP_KEY}
        {--force : Forzar la ejecución sin confirmación en producción}';

    /** @var array<string> */
    protected $aliases = ['xerintel:clear'];

    protected $description = 'Limpia todas las cachés, colas, regenera la clave del .env de forma segura y recompone el autoload';

    public function handle(): int
    {
        $this->info('Iniciando limpieza completa del proyecto...');

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
        $this->avisarSiLaClaveNoCoincide();

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

        // 2. Limpieza de colas
        $this->line('▶ Limpiando colas de trabajo (queue:clear)...');
        try {
            $this->call('queue:clear', ['--force' => true]);
        } catch (\Throwable $e) {
            $this->warn('Aviso al limpiar colas: '.$e->getMessage());
        }

        // 3. Regeneración de clave segura en .env
        if (! $this->option('no-key')) {
            // ⚠️ REGENERAR POR DEFECTO ES INTENCIONADO. NO INVERTIRLO.
            //
            // Cada auditoría propone lo mismo: que la clave sólo se regenere
            // con un flag explícito, porque «el comportamiento por defecto es
            // destructivo». Y sí lo es: ése es justamente el propósito del
            // comando. `project:clear` deja el proyecto como recién instalado;
            // conservar la clave sería hacer media limpieza.
            //
            // Las salvaguardas ya están puestas donde tienen que estar: en
            // producción pide confirmación explícita, avisa de cuántos usuarios
            // tienen 2FA, y existe `--no-key` para quien quiera limpiar sin
            // tocar la clave.
            //
            // Está decidido y revisado varias veces. Ver
            // docs/info/decisiones-tecnicas.md D15 antes de volver a proponerlo.
            //
            // Lo que no se ve venir es el 2FA: Fortify guarda
            // `two_factor_secret` CIFRADO con la APP_KEY, así que quien lo tenga
            // activo se queda sin poder completar el segundo factor y hay que
            // volver a darlo de alta. Los tokens de Sanctum no se ven afectados
            // porque se guardan hasheados, no cifrados.
            $con2fa = $this->contarUsuariosCon2fa();

            if ($con2fa > 0) {
                $this->warn(
                    "Aviso: {$con2fa} usuario(s) tienen el doble factor activo. Al cambiar la APP_KEY "
                    .'su `two_factor_secret` deja de poder descifrarse y tendrán que volver a configurarlo.'
                );
            }

            $regenerar = true;

            if (app()->environment('production') && ! $this->option('force')) {
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

        // 5. Recacheo opcional para producción
        if ($this->option('production')) {
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
     * Cuántas cuentas tienen el doble factor configurado.
     *
     * Consulta directa para no depender del modelo ni de sus casts, y tolerante
     * a fallos: es un aviso, no puede tumbar la limpieza (por ejemplo, si se
     * ejecuta sin base de datos disponible).
     */
    private function contarUsuariosCon2fa(): int
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
    private function avisarSiLaClaveNoCoincide(): void
    {
        $cache = base_path('bootstrap/cache/config.php');

        if (! is_file($cache)) {
            return;
        }

        try {
            $cacheada = (require $cache)['app']['key'] ?? null;
        } catch (\Throwable) {
            return;
        }

        // Se lee el fichero, no `env()` ni `config()`.
        //
        // `config('app.key')` devolvería justo la cacheada, que es el otro lado
        // de la comparación. Y `env()` tampoco vale: con la configuración
        // cacheada Laravel se salta la carga del `.env` y devuelve null. Lo
        // único que dice la verdad aquí es el fichero.
        $delEnv = $this->claveDelFicheroEnv();

        if (! is_string($cacheada) || ! is_string($delEnv) || $cacheada === $delEnv) {
            return;
        }

        $this->warn('⚠ La APP_KEY de la caché de configuración NO es la del .env.');
        $this->line('  La aplicación está funcionando con la cacheada. Al limpiar pasará a usar la');
        $this->line('  del .env, y lo cifrado con la otra —sesiones, cookies, doble factor— dejará');
        $this->line('  de descifrarse. Si lo que quieres es conservar la que funciona ahora, cópiala');
        $this->line('  al .env antes de seguir:');
        $this->newLine();
        $this->line('    '.$cacheada);
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
    private function claveDelFicheroEnv(): ?string
    {
        $ruta = base_path('.env');

        if (! is_file($ruta)) {
            return null;
        }

        $contenido = (string) file_get_contents($ruta);

        if (preg_match('/^APP_KEY\s*=\s*"?([^"\r\n]*)"?/m', $contenido, $coincidencias) !== 1) {
            return null;
        }

        $clave = trim($coincidencias[1]);

        return $clave === '' ? null : $clave;
    }
}
