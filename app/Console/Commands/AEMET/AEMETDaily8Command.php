<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETHighSea;
use App\Models\WeatherStation\AEMET\AEMETPredictionBeach;
use App\Models\WeatherStation\AEMET\AEMETSunRadiation;
use Illuminate\Console\Command;

class AEMETDaily8Command extends Command
{
    use ValidatesAemetPayload;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update-daily8';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial cada 24h';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "\n\n Comenzando actualización de datos de AEMET \n\n";

        $this->guardedSave('beach_regla', fn () => \AEMETHelper::getPredictionBeachById(1101604), [AEMETPredictionBeach::class, 'saveFromApi']);
        $this->guardedSave('beach_cruz', fn () => \AEMETHelper::getPredictionBeachById(1101602), [AEMETPredictionBeach::class, 'saveFromApi']);
        $this->guardedSave('altamar', fn () => \AEMETHelper::getAltamarPrediction(), [AEMETHighSea::class, 'saveFromApi']);
        $this->guardedSave('sun_radiation', fn () => \AEMETHelper::getSunRadiation(), [AEMETSunRadiation::class, 'saveFromApi']);

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
