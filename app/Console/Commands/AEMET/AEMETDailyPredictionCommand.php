<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETDailyPrediction;
use Illuminate\Console\Command;

/**
 * Predicción diaria del municipio: resumen "hoy/mañana", hasta 7 días por
 * sondeo. Complementa a `aemet:hourly-prediction`. Publicación 4 veces/día.
 */
class AEMETDailyPredictionCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:daily-prediction';

    protected $description = 'Predicción diaria del municipio (resumen por día)';

    public function handle(): int
    {
        $this->info('AEMET · predicción diaria: comenzando.');

        $this->guardedSave('daily-prediction', fn () => \AEMETHelper::getDailyPrediction(), [AEMETDailyPrediction::class, 'saveFromApi']);

        $this->info('AEMET · predicción diaria: terminado.');

        return self::SUCCESS;
    }
}
