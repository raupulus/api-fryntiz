<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETCoast;
use Illuminate\Console\Command;

/**
 * Predicción costera.
 *
 * AEMET la rehace dos veces al día, así que el planificador llama a este mismo
 * comando a las 12:00 y a las 20:00. Antes había dos comandos distintos
 * (`update-daily12` y `update-daily20`) que hacían exactamente la misma
 * llamada.
 */
class AEMETCoastCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:coast';

    protected $description = 'Predicción costera';

    public function handle(): int
    {
        $this->info('AEMET · costa: comenzando.');

        $this->guardedSave('costa', fn () => \AEMETHelper::getCostaPrediction(), [AEMETCoast::class, 'saveFromApi']);

        $this->info('AEMET · costa: terminado.');

        return self::SUCCESS;
    }
}
