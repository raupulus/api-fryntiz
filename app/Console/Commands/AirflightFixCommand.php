<?php

namespace App\Console\Commands;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\KeyCounter\Keyboard;
use Carbon\Carbon;
use Carbon\Traits\Creator;
use Illuminate\Console\Command;

/**
 * Class KeyCounterGenerateDuration
 *
 * @package App\Console\Commands
 */
class AirflightFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'airflight:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza correcciones debido a errores en el desarrollo o nuevas implementaciones';

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
     * Añade el icono de la bandera y también el país a los aviones
     * que les falte uno de esos campos.
     */
    private function fixAirplaneFlagsAndCountries()
    {
        $query = AirFlightAirPlane::whereNull('country')->orWhereNull('flag');

        $airflightsCount = $query->count();

        echo "\nSe van a actualizar: " . $airflightsCount . " aviones.\n";

        $position = 0;
        $updated = 0;

        while($airflightsCount > $position) {
            $airflights = $query->limit(100)->get();

            echo "\nConsultando nuevos aviones: " . $airflights->count() . " \n";

            foreach ($airflights as $airflight) {
                $hex = AirFlightAirPlane::searchHex($airflight->icao);

                if ($hex) {
                    echo "\nActualizando avión con id: " . $airflight->id .
                        " código ICAO: " . $airflight->icao . "\n";

                    $airflight->flag = $hex['flag_image'];
                    $airflight->country = $hex['country'];

                    if ($airflight->save()) {
                        $updated++;
                    }
                }
            }

            $position += 100;
        }

        echo "\nSe han actualizado: " . $updated . " aviones de " . $airflightsCount . ".\n";
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "\n\nComenzando a Reparar y Añadir características en Aviones\n\n";
        $this->fixAirplaneFlagsAndCountries();
        echo "\n\nTermina de Reparar y Añadir características en Aviones\n\n";
    }
}
