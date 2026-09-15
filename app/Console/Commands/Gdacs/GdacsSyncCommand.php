<?php

declare(strict_types=1);

namespace App\Console\Commands\Gdacs;

use App\Services\Gdacs\GdacsService;
use Illuminate\Console\Command;

/**
 * Sondea GDACS y guarda los sucesos dentro del radio configurado
 * (`config('gdacs.reference')`). Pensado para correr cada 10 minutos —
 * ver `routes/console.php`.
 */
class GdacsSyncCommand extends Command
{
    protected $signature = 'gdacs:sync';

    protected $description = 'Sondea GDACS y guarda los sucesos dentro del radio de interés configurado';

    public function handle(GdacsService $service): int
    {
        $this->info('GDACS: comenzando sondeo.');

        $result = $service->sync();

        $this->info("GDACS: {$result['fetched']} sucesos recibidos, {$result['kept']} dentro del radio.");

        return self::SUCCESS;
    }
}
