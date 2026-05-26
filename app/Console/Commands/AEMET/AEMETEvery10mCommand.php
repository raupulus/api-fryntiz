<?php

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETContamination;
use Illuminate\Console\Command;

class AEMETEvery10mCommand extends Command
{
    use ValidatesAemetPayload;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update-every10m';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial cada 10 minutos';

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


        $this->guardedSave('contaminacion', fn () => \AEMETHelper::getContamination(), [AEMETContamination::class, 'saveFromApi']);

        /*********** Cada Lo mínimo que pueda ***********/
        //$response = (new AEMET())->getPredictionDaily();  // NO PREPARADO MODELO/MIGRACION

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
