<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class ForceClearCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'force:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fuerza limpieza de caché';


    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer(): string
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" ' . getcwd() . '/composer.phar';
        }

        return 'composer';
    }


    /**
     * Performs general cleanup operations.
     *
     * @param Filesystem $filesystem The instance of the Filesystem class.
     * @return void
     */
    public function handle(Filesystem $filesystem): void
    {
        echo('');

        $this->info('Estamos de limpieza general, espera un momento mientras se aplican las operaciones');


        $this->info('Limpiando caché con Cache::flush');
        Cache::flush();

        $this->info('Limpiando caché general');
        Artisan::call('cache:clear');

        $this->info('Limpiando caché de configuraciones');
        Artisan::call('config:cache');
        Artisan::call('config:clear');

        $this->info('Limpiando caché de vistas');

        Artisan::call('view:clear');

        $this->info('Limpiando caché para la barra de debug');
        Artisan::call('debugbar:clear');

        $this->info('Limpiando caché de rutas');
        Artisan::call('route:clear');

        $this->info('Limpiando optimizaciones');
        Artisan::call('optimize:clear');

        $this->info('Descubriendo paquetes nuevos');
        Artisan::call('package:discover');

        $this->info('Limpiando colas (QUEUE)');
        Artisan::call('queue:clear');

        $this->info('Recomponiendo Key');
        Artisan::call('key:generate');


        $this->info('Recomponiendo índice de composer (composer dump-autoload)');

        $composer = $this->findComposer();

        $process = new Process([$composer.' dump-autoload']);
        $process->setTimeout(null);


        echo('');

        $this->info('El caché de tu aplicación ha quedado más limpio que una sábana nueva');

        echo('');
    }

}
