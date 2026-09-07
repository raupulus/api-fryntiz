<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `project:clear` decide por `APP_ENV`.
 *
 * Lo que fija este test es que `php artisan project:clear` a secas hace lo
 * correcto en los dos lados, que es el motivo de existir del comando: en
 * producción no toca la APP_KEY, no vacía las colas y recachea; fuera de
 * producción se comporta como siempre.
 *
 * Los casos de producción restauran `bootstrap/cache/` al terminar: el comando
 * recachea de verdad, y lo que cachearía aquí es la configuración del entorno de
 * pruebas.
 */
class ProjectClearCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $cacheOriginal = [];

    private ?string $envOriginal = null;

    protected function tearDown(): void
    {
        $this->restaurarCacheDeArranque();
        $this->restaurarEnv();

        parent::tearDown();
    }

    public function test_fuera_de_produccion_regenera_la_clave_sin_pedir_nada(): void
    {
        // Con `--no-key` para no tocar el `.env` de quien ejecute los tests; lo
        // que se comprueba es que no aparece el aviso de conservación, o sea que
        // la decisión por defecto sigue siendo regenerar.
        $this->artisan('project:clear', ['--no-key' => true])
            ->doesntExpectOutputToContain('Entorno «production»')
            ->assertExitCode(0);
    }

    public function test_en_produccion_conserva_la_clave_sin_flags(): void
    {
        $this->simularProduccion();

        $this->artisan('project:clear')
            ->expectsOutputToContain('Entorno «production»')
            ->expectsOutputToContain('Se conserva la APP_KEY actual.')
            ->doesntExpectOutputToContain('Regenerando clave de aplicación')
            ->assertExitCode(0);
    }

    public function test_en_produccion_no_vacia_las_colas(): void
    {
        $this->simularProduccion();

        // `queue:clear` borra la tabla `jobs`: correos sin enviar, PDFs sin
        // generar. Un despliegue no tira trabajo pendiente.
        $this->artisan('project:clear')
            ->doesntExpectOutputToContain('queue:clear')
            ->expectsOutputToContain('queue:restart')
            ->assertExitCode(0);
    }

    public function test_en_produccion_recachea_sin_pasar_production(): void
    {
        $this->simularProduccion();

        $this->artisan('project:clear')
            ->expectsOutputToContain('Recacheando optimizaciones para producción')
            ->assertExitCode(0);

        $this->assertTrue(
            File::exists(base_path('bootstrap/cache/config.php')),
            'En producción el comando tiene que dejar la configuración cacheada.'
        );
    }

    public function test_en_produccion_la_clave_se_regenera_solo_pidiendola(): void
    {
        $this->simularProduccion();

        // Este caso SÍ ejecuta `key:generate --force`, que reescribe el `.env`
        // del proyecto. Se guarda antes y se devuelve tal cual en `tearDown()`:
        // sin esto, correr la suite le cambia a cualquiera la APP_KEY de su
        // entorno local y se queda sin poder descifrar lo que tuviera cifrado.
        $this->protegerEnv();

        $this->artisan('project:clear', ['--key' => true, '--force' => true])
            ->expectsOutputToContain('Regenerando clave de aplicación')
            ->assertExitCode(0);
    }

    public function test_no_key_manda_sobre_key(): void
    {
        $this->simularProduccion();

        $this->artisan('project:clear', ['--key' => true, '--no-key' => true, '--force' => true])
            ->expectsOutputToContain('Se conserva la APP_KEY actual.')
            ->assertExitCode(0);
    }

    /**
     * Pone la aplicación en «production» y guarda las cachés de arranque para
     * devolverlas como estaban.
     */
    private function simularProduccion(): void
    {
        $this->guardarCacheDeArranque();

        $this->app->detectEnvironment(static fn () => 'production');
    }

    /**
     * Guarda el `.env` para devolverlo intacto pase lo que pase en el test.
     */
    private function protegerEnv(): void
    {
        $ruta = base_path('.env');

        if (File::exists($ruta)) {
            $this->envOriginal = (string) File::get($ruta);
        }
    }

    private function restaurarEnv(): void
    {
        if ($this->envOriginal === null) {
            return;
        }

        File::put(base_path('.env'), $this->envOriginal);
        $this->envOriginal = null;
    }

    private function guardarCacheDeArranque(): void
    {
        foreach (File::glob(base_path('bootstrap/cache/*.php')) as $fichero) {
            $this->cacheOriginal[$fichero] = (string) File::get($fichero);
        }
    }

    private function restaurarCacheDeArranque(): void
    {
        if ($this->cacheOriginal === []) {
            return;
        }

        foreach (File::glob(base_path('bootstrap/cache/*.php')) as $fichero) {
            if (! array_key_exists($fichero, $this->cacheOriginal)) {
                File::delete($fichero);
            }
        }

        foreach ($this->cacheOriginal as $fichero => $contenido) {
            File::put($fichero, $contenido);
        }

        $this->cacheOriginal = [];
    }

    /**
     * El despliegue del 2026-09-07 se quedó a medias con «Please provide a
     * valid cache path»: `view:cache` aborta si no existe
     * `storage/framework/views`, y para entonces las cachés ya estaban
     * borradas. El sitio se queda sin ninguna.
     */
    public function test_crea_los_directorios_de_trabajo_que_falten(): void
    {
        $views = storage_path('framework/views');
        $bootstrap = base_path('bootstrap/cache');

        // Se guarda lo que haya dentro para devolverlo tal cual.
        $este = $this;
        $restaurar = [];

        foreach ([$views, $bootstrap] as $directorio) {
            if (is_dir($directorio)) {
                $restaurar[] = $directorio;
                @rmdir($directorio);
            }
        }

        try {
            $this->artisan('project:clear --force --no-key')->assertSuccessful();

            $este->assertDirectoryExists($views);
            $este->assertDirectoryExists($bootstrap);
            $este->assertDirectoryExists(storage_path('framework/cache/data'));
        } finally {
            foreach ($restaurar as $directorio) {
                if (! is_dir($directorio)) {
                    @mkdir($directorio, 0775, true);
                }
            }
        }
    }
}
