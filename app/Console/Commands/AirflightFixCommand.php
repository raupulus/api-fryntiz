<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Console\Command;

/**
 * Class KeyCounterGenerateDuration
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
        $airflightsCount = AirFlightAirPlane::whereNull('country')->orWhereNull('flag')->count();

        echo "\nSe van a revisar: ".$airflightsCount." aviones.\n";

        $updated = 0;
        $excludedIds = [];

        while (true) {
            $airflights = AirFlightAirPlane::where(function ($query) {
                $query->whereNull('country')->orWhereNull('flag');
            })
                ->whereNotIn('id', $excludedIds)
                ->limit(100)
                ->get();

            if ($airflights->isEmpty()) {
                break;
            }

            echo "\nConsultando nuevos aviones: ".$airflights->count()." \n";

            foreach ($airflights as $airflight) {
                $hex = AirFlightAirPlane::searchHex($airflight->icao);

                if ($hex) {
                    echo "\nActualizando avión con id: ".$airflight->id.
                        ' código ICAO: '.$airflight->icao."\n";

                    $airflight->flag = $hex['flag_image'];
                    $airflight->country = $hex['country'];

                    if ($airflight->save()) {
                        $updated++;
                    }
                } else {
                    // Sin bandera correspondiente en FLAGS (ICAO reservado o
                    // fuera de rango): se excluye para no volver a
                    // consultarlo en la siguiente página, si no el bucle no
                    // avanza nunca sobre estos y deja sin revisar aviones
                    // que sí eran corregibles.
                    $excludedIds[] = $airflight->id;
                }
            }
        }

        echo "\nSe han actualizado: ".$updated.' aviones de '.$airflightsCount.".\n";
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
