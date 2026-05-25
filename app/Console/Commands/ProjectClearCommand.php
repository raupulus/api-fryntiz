<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProjectClearCommand extends Command
{
    protected $signature = 'project:clear
        {--production : Recachear despues de limpiar}';

    protected $description = 'Limpiar todas las caches del proyecto';

    public function handle(): int
    {
        $this->info('Limpiando caches del proyecto...');

        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('event:clear');
        $this->call('clear-compiled');

        if ($this->option('production')) {
            $this->newLine();
            $this->info('Recacheando para produccion...');
            $this->call('config:cache');
            $this->call('route:cache');
            $this->call('view:cache');
            $this->call('event:cache');
        }

        $this->newLine();
        $this->info('Todas las caches han sido limpiadas correctamente.');
        return self::SUCCESS;
    }
}
