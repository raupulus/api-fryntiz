<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETSunRadiation;
use Illuminate\Console\Command;

/**
 * Radiación solar acumulada. Publicación diaria.
 */
class AEMETSunRadiationCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:sun-radiation';

    protected $description = 'Radiación solar acumulada diaria';

    public function handle(): int
    {
        $this->info('AEMET · radiación solar: comenzando.');

        $this->guardedSave('radiacion_solar', fn () => \AEMETHelper::getSunRadiation(), [AEMETSunRadiation::class, 'saveFromApi']);

        $this->info('AEMET · radiación solar: terminado.');

        return self::SUCCESS;
    }
}
