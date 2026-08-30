<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETPrediction;
use Illuminate\Console\Command;

/**
 * Predicción horaria del municipio.
 *
 * AEMET declara que la rehace cada 3 horas; se pide cada 4 para no ir pegado al
 * borde y no gastar cuota en llamadas que devolverían lo mismo.
 */
class AEMETHourlyPredictionCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:hourly-prediction';

    protected $description = 'Predicción horaria del municipio';

    public function handle(): int
    {
        $this->info('AEMET · predicción horaria: comenzando.');

        $this->guardedSave('prediccion_horaria', fn () => \AEMETHelper::getPredictionHourly(), [AEMETPrediction::class, 'saveFromApi']);

        $this->info('AEMET · predicción horaria: terminado.');

        return self::SUCCESS;
    }
}
