<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Content\ContentFileUsageService;
use Illuminate\Console\Command;

/**
 * Tarea diaria (C2 de la auditoría de contenidos del 2026-09-24): borra los
 * ficheros de contenido que llevan 30 días sin usar —el fichero, sus copias
 * pequeñas y su fila—, comprobando antes en toda la base que nada los usa.
 */
class ContentPurgeUnusedFilesCommand extends Command
{
    protected $signature = 'content:purge-unused-files';

    protected $description = 'Borrar los ficheros de contenido que llevan 30 días sin usar (fichero, miniaturas y fila)';

    public function handle(ContentFileUsageService $usage): int
    {
        $result = $usage->purge();

        $this->info(sprintf(
            'Ficheros borrados: %d. Conservados porque algo los usa: %d.',
            $result['deleted'],
            $result['kept'],
        ));

        return self::SUCCESS;
    }
}
