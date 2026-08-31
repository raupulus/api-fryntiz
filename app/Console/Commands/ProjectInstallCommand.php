<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProjectInstallCommand extends Command
{
    protected $signature = 'project:install
        {--force : Ejecutar sin confirmacion}
        {--seed : Ejecutar seeders despues de las migraciones}
        {--fresh : Ejecutar migrate:fresh en lugar de migrate}';

    /** @var array<string> */
    protected $aliases = ['xerintel:install'];

    protected $description = 'Inicializar el proyecto en un entorno de desarrollo';

    public function handle(): int
    {
        $this->info('=== Api Raupulus - Project Install ===');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Deseas inicializar el proyecto?', true)) {
            $this->warn('Instalacion cancelada.');

            return self::SUCCESS;
        }

        if (! file_exists(base_path('.env'))) {
            $this->warn('No se encontro archivo .env. Copiando .env.example...');
            copy(base_path('.env.example'), base_path('.env'));
            $this->info('Archivo .env creado');
        }

        $this->info('Generando clave de aplicación...');
        $this->call('key:generate', ['--force' => true]);

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->call('migrate', ['--force' => true]);
        }
        $this->info('Migraciones ejecutadas');

        if ($this->option('seed') || $this->option('fresh')) {
            $this->call('db:seed', ['--force' => true]);
            $this->info('Seeders ejecutados');
        }

        $this->call('storage:link');
        $this->call('project:clear');

        $this->newLine();
        $this->info('Proyecto instalado correctamente. Ejecuta: php artisan serve');

        return self::SUCCESS;
    }
}
