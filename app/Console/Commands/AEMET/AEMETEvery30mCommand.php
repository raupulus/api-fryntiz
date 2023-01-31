<?php

namespace App\Console\Commands\AEMET;

use App\Models\WeatherStation\AEMETAdverseEvents;
use Illuminate\Console\Command;

class AEMETEvery30mCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update-every30m';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial cada 30 minutos';

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

        // Cuando se emite fenómeno, preferente: 09:00, 11:30, 23:00 y 23:50
        AEMETAdverseEvents::saveFromApi(\AMETHelper::getAvisosCap());

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
