<?php

declare(strict_types=1);

namespace App\Console\Commands\WeatherStation;

use App\Models\WeatherStation\OpenMeteo\OpenMeteoMarineTide;
use App\Services\WeatherStation\OpenMeteoMarineService;
use App\Support\WeatherStation\TideExtremesCalculator;
use Illuminate\Console\Command;

/**
 * Pide la altura del mar horaria a Open-Meteo Marine, calcula las pleamares y
 * bajamares y las guarda. Pensado para correr dos veces al día — ver
 * `routes/console.php`: la marea es astronómicamente predecible y Open-Meteo
 * no rehace su modelo más a menudo que eso.
 */
class OpenMeteoMarineSyncCommand extends Command
{
    protected $signature = 'marine:sync';

    protected $description = 'Calcula y guarda las próximas pleamares/bajamares de Chipiona (Open-Meteo Marine)';

    public function handle(OpenMeteoMarineService $service): int
    {
        $this->info('Open-Meteo Marine: comenzando.');

        $payload = $service->fetchSeaLevelHeights();

        if ($payload === null) {
            $this->error('Open-Meteo Marine: sin datos, ver log.');

            return self::FAILURE;
        }

        $extremes = TideExtremesCalculator::extremes(
            $payload['times'],
            $payload['heights'],
            config('open_meteo_marine.timezone'),
        );

        $saved = OpenMeteoMarineTide::saveExtremes($extremes, $payload['generated_at']);

        $this->info(count($saved).' extremos de marea guardados.');

        return self::SUCCESS;
    }
}
