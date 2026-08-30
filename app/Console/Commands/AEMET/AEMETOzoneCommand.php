<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETOzone;
use Illuminate\Console\Command;

/**
 * Ozono en superficie. Publicación diaria.
 */
class AEMETOzoneCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:ozone';

    protected $description = 'Ozono en superficie';

    public function handle(): int
    {
        $this->info('AEMET · ozono: comenzando.');

        $this->guardedSave('ozono', fn () => \AEMETHelper::getOzone(), [AEMETOzone::class, 'saveFromApi']);

        $this->info('AEMET · ozono: terminado.');

        return self::SUCCESS;
    }
}
