<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETPredictionBeach;
use Illuminate\Console\Command;

/**
 * Predicción de playas.
 *
 * Dos playas de Chipiona: La Regla (1101604) y La Cruz del Mar (1101602).
 * AEMET publica una vez al día.
 */
class AEMETBeachesCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:beaches';

    protected $description = 'Predicción de las playas de La Regla y La Cruz del Mar';

    public function handle(): int
    {
        $this->info('AEMET · playas: comenzando.');

        $this->guardedSave('playa_la_regla', fn () => \AEMETHelper::getPredictionBeachById(1101604), [AEMETPredictionBeach::class, 'saveFromApi']);
        $this->guardedSave('playa_cruz_del_mar', fn () => \AEMETHelper::getPredictionBeachById(1101602), [AEMETPredictionBeach::class, 'saveFromApi']);

        $this->info('AEMET · playas: terminado.');

        return self::SUCCESS;
    }
}
