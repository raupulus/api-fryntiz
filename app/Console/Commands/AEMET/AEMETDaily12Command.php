<?php

namespace App\Console\Commands\AEMET;

use App\Models\WeatherStation\AEMETCoast;
use App\Models\WeatherStation\AEMETOzone;
use Illuminate\Console\Command;

class AEMETDaily12Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update-daily12';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial a las 12:00';

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

        // Devuelve  UV máximo para la provincia. Por ahora no usado
        //$response = \AMETHelper::getUviInfo();

        // Obtiene predicciones de costa, zona de Cádiz/huelva (Parece renovar dos veces al día: 12:00 y 20:00)
        AEMETCoast::saveFromApi(\AMETHelper::getCostaPrediction());

        ## Pide los datos para el ozono obtenidos mediante una ozonosonda
        AEMETOzone::saveFromApi(\AMETHelper::getOzone()); // Obtiene datos de ozono, parece ser lanzado globo y registrado una vez a la semana sobre las 11:00 los miércoles.

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
