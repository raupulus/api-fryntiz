<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETCoast;
use Illuminate\Console\Command;

class AEMETDaily20Command extends Command
{
    use ValidatesAemetPayload;

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

        $this->guardedSave('costa_20h', fn () => \AEMETHelper::getCostaPrediction(), [AEMETCoast::class, 'saveFromApi']);

        echo "\n\n Fin actualización de datos de AEMET \n\n";
    }
}
