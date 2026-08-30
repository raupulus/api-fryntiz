<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETHighSea;
use Illuminate\Console\Command;

/**
 * Predicción de alta mar.
 *
 * Publicación diaria. El panel la tenía apuntando a `aemet:update-every4h`, que
 * no traía alta mar sino la predicción horaria: el botón existía y no hacía
 * lo que decía.
 */
class AEMETHighSeaCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:high-sea';

    protected $description = 'Predicción de alta mar';

    public function handle(): int
    {
        $this->info('AEMET · alta mar: comenzando.');

        $this->guardedSave('altamar', fn () => \AEMETHelper::getAltamarPrediction(), [AEMETHighSea::class, 'saveFromApi']);

        $this->info('AEMET · alta mar: terminado.');

        return self::SUCCESS;
    }
}
