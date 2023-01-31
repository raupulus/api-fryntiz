<?php

namespace App\Console\Commands;

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



        // Plantear para en el futuro obtener todos los registros entre
        // 1980-2010
        //$response = (new AEMET())->getPeriodClimatologiaPasada($lastMonth, $now);

        // Imágenes, por si en el futuro las usara
        //$response = (new AEMET())->getImageMarTemperature();
        //$response = (new AEMET())->getImageVegetation();


        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
