<?php

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETCoast;
use App\Models\WeatherStation\AEMET\AEMETOzone;
use Illuminate\Console\Command;

class AEMETDaily12Command extends Command
{
    use ValidatesAemetPayload;

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
        //$response = \AEMETHelper::getUviInfo();

        $this->guardedSave('costa_12h', fn () => \AEMETHelper::getCostaPrediction(), [AEMETCoast::class, 'saveFromApi']);
        $this->guardedSave('ozono', fn () => \AEMETHelper::getOzone(), [AEMETOzone::class, 'saveFromApi']);

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
