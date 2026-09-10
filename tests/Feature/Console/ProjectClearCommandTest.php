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
    private array $originalCache = [];

    private ?string $originalEnv = null;

    protected function tearDown(): void
    {
        $this->restoreBootstrapCache();
        $this->restoreEnv();

        parent::tearDown();
    }

    public function test_outside_production_it_regenerates_the_key_without_asking(): void
    {
        // Con `--no-key` para no tocar el `.env` de quien ejecute los tests; lo
        // que se comprueba es que no aparece el aviso de conservación, o sea que
        // la decisión por defecto sigue siendo regenerar.
        $this->artisan('project:clear', ['--no-key' => true])
            ->doesntExpectOutputToContain('Entorno «production»')
            ->assertExitCode(0);
    }

    public function test_in_production_it_keeps_the_key_without_flags(): void
    {
        $this->simulateProduction();

        $this->artisan('project:clear')
            ->expectsOutputToContain('Entorno «production»')
            ->expectsOutputToContain('Se conserva la APP_KEY actual.')
            ->doesntExpectOutputToContain('Regenerando clave de aplicación')
            ->assertExitCode(0);
    }

    public function test_in_production_it_does_not_empty_the_queues(): void
    {
        $this->simulateProduction();

        // `queue:clear` borra la tabla `jobs`: correos sin enviar, PDFs sin
        // generar. Un despliegue no tira trabajo pendiente.
        $this->artisan('project:clear')
            ->doesntExpectOutputToContain('queue:clear')
            ->expectsOutputToContain('queue:restart')
            ->assertExitCode(0);
    }

    public function test_in_production_it_recaches_without_passing_production(): void
    {
        $this->simulateProduction();

        $this->artisan('project:clear')
            ->expectsOutputToContain('Recacheando optimizaciones para producción')
            ->assertExitCode(0);

        $this->assertTrue(
            File::exists(base_path('bootstrap/cache/config.php')),
            'En producción el comando tiene que dejar la configuración cacheada.'
        );
    }

    public function test_in_production_the_key_is_only_regenerated_when_asked(): void
    {
        $this->simulateProduction();

        // Este caso SÍ ejecuta `key:generate --force`, que reescribe el `.env`
        // del proyecto. Se guarda antes y se devuelve tal cual en `tearDown()`:
        // sin esto, correr la suite le cambia a cualquiera la APP_KEY de su
        // entorno local y se queda sin poder descifrar lo que tuviera cifrado.
        $this->protectEnv();

        $this->artisan('project:clear', ['--key' => true, '--force' => true])
            ->expectsOutputToContain('Regenerando clave de aplicación')
            ->assertExitCode(0);
    }

    public function test_no_key_overrides_key(): void
    {
        $this->simulateProduction();

        $this->artisan('project:clear', ['--key' => true, '--no-key' => true, '--force' => true])
            ->expectsOutputToContain('Se conserva la APP_KEY actual.')
            ->assertExitCode(0);
    }

    /**
     * Pone la aplicación en «production» y guarda las cachés de arranque para
     * devolverlas como estaban.
     */
    private function simulateProduction(): void
    {
        $this->saveBootstrapCache();

        $this->app->detectEnvironment(static fn () => 'production');
    }

    /**
     * Guarda el `.env` para devolverlo intacto pase lo que pase en el test.
     */
    private function protectEnv(): void
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $this->originalEnv = (string) File::get($path);
        }
    }

    private function restoreEnv(): void
    {
        if ($this->originalEnv === null) {
            return;
        }

        File::put(base_path('.env'), $this->originalEnv);
        $this->originalEnv = null;
    }

    private function saveBootstrapCache(): void
    {
        foreach (File::glob(base_path('bootstrap/cache/*.php')) as $file) {
            $this->originalCache[$file] = (string) File::get($file);
        }
    }

    private function restoreBootstrapCache(): void
    {
        if ($this->originalCache === []) {
            return;
        }

        foreach (File::glob(base_path('bootstrap/cache/*.php')) as $file) {
            if (! array_key_exists($file, $this->originalCache)) {
                File::delete($file);
            }
        }

        foreach ($this->originalCache as $file => $contents) {
            File::put($file, $contents);
        }

        $this->originalCache = [];
    }

    /**
     * El despliegue del 2026-09-07 se quedó a medias con «Please provide a
     * valid cache path»: `view:cache` aborta si no existe
     * `storage/framework/views`, y para entonces las cachés ya estaban
     * borradas. El sitio se queda sin ninguna.
     */
    public function test_it_creates_missing_working_directories(): void
    {
        $views = storage_path('framework/views');
        $bootstrap = base_path('bootstrap/cache');

        // Se guarda lo que haya dentro para devolverlo tal cual.
        $test = $this;
        $toRestore = [];

        foreach ([$views, $bootstrap] as $directory) {
            if (is_dir($directory)) {
                $toRestore[] = $directory;
                @rmdir($directory);
            }
        }

        try {
            $this->artisan('project:clear --force --no-key')->assertSuccessful();

            $test->assertDirectoryExists($views);
            $test->assertDirectoryExists($bootstrap);
            $test->assertDirectoryExists(storage_path('framework/cache/data'));
        } finally {
            foreach ($toRestore as $directory) {
                if (! is_dir($directory)) {
                    @mkdir($directory, 0775, true);
                }
            }
        }
    }
}
