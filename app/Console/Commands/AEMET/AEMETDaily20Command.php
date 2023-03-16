<?php

namespace App\Console\Commands\AEMET;

use App\Models\WeatherStation\AEMETCoast;
use Illuminate\Console\Command;

class AEMETDaily20Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update-daily20';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial a las 20:00';

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
     */
    public function handle()
    {
        echo "\n\n Comenzando actualización de datos de AEMET \n\n";

        // Obtiene predicciones de costa, zona de Cádiz/huelva (Parece renovar dos veces al día: 12:00 y 20:00)
        AEMETCoast::saveFromApi(\AEMETHelper::getCostaPrediction());

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
