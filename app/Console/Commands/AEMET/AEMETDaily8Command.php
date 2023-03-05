<?php

namespace App\Console\Commands\AEMET;

use App\Models\WeatherStation\AEMETHighSea;
use App\Models\WeatherStation\AEMETPredictionBeach;
use App\Models\WeatherStation\AEMETSunRadiation;
use Illuminate\Console\Command;

class AEMETDaily8Command extends Command
{
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

        ## Playa de Regla
        AEMETPredictionBeach::saveFromApi(\AEMETHelper::getPredictionBeachById(1101604));

        ## Playa Cruz del Mar
        AEMETPredictionBeach::saveFromApi(\AEMETHelper::getPredictionBeachById(1101602));

        ## Obtiene predicciones de alta mar, zona de Cádiz (Parece renovar a las 8:00)
        AEMETHighSea::saveFromApi(\AEMETHelper::getAltamarPrediction());

        ## Obtiene predicciones de radiación solar
        AEMETSunRadiation::saveFromApi(\AEMETHelper::getSunRadiation());

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
