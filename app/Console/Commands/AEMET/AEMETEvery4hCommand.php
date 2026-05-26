<?php

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETPrediction;
use Illuminate\Console\Command;

class AEMETEvery4hCommand extends Command
{
    use ValidatesAemetPayload;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aemet:update-every4h';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los datos desde la api REST de AEMET oficial cada 4 horas';

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

        $this->guardedSave('prediccion_4h', fn () => \AEMETHelper::getPredictionHourly(), [AEMETPrediction::class, 'saveFromApi']);

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
