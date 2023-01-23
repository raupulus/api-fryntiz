<?php

namespace App\Console\Commands;

use AMETHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AEMETCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial';

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

        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();


        // Cada 10m
        $response = AMETHelper::getAvisosCap(); //Cuando se emite fenómeno, preferente: 09:00, 11:30, 23:00 y 23:50



        // Cada (LO MINIMO QUE PUEDA)


        // CADA HORA
        //$response = (new AEMET())->getPredictionDaily();



        //CADA DIA
        //$response = (new AEMET())->getPredictionBeachById(1101604); // Playa de regla
        //$response = (new AEMET())->getPredictionBeachById(1101602); // Cruz del mar








        //$response = (new AEMET())->getPredictionHourly();
        //$response = (new AEMET())->getUviInfo();
        //$response = (new AEMET())->getPeriodClimatologiaPasada($lastMonth, $now);
        //$response = (new AEMET())->getImageMarTemperature();
        //$response = (new AEMET())->getImageVegetation();
        //$response = (new AEMET())->getAltamarPrediction();
        //$response = (new AEMET())->getCostaPrediction();
        //$response = (new AEMET())->getContamination(); // Desde doñana, cada 10m
        //$response = (new AEMET())->getOzono();
        //$response = (new AEMET())->getSunRadiation(); // Datos horarios (HORA SOLAR VERDADERA) acumulados de radiación global, directa, difusa e infrarroja, y datos semihorarios (HORA SOLAR VERDADERA) acumulados de radiación ultravioleta eritemática.Datos diarios acumulados de radiación global, directa, difusa, ultravioleta eritemática e infrarroja. Periodicidad: Cada 24h (actualmente en fines de semana, festivos y vacaciones, no se genera por la ausencia de personal en el Centro Radiométrico Nacional).









        //$response = (new AEMET())->test();

        dd(['response' => $response]);


        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
