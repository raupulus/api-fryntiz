<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Console\Command;

/**
 * Class AirflightFixCommand
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
    private function fixAirplaneFlagsAndCountries(): void
    {
        $airflightsCount = AirFlightAirPlane::whereNull('country')->orWhereNull('flag')->count();

        $this->line('Se van a revisar: '.$airflightsCount.' aviones.');

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

            $this->line('Consultando nuevos aviones: '.$airflights->count());

            foreach ($airflights as $airflight) {
                $hex = AirFlightAirPlane::searchHex($airflight->icao);

                if ($hex) {
                    $this->line('Actualizando avión con id: '.$airflight->id.' código ICAO: '.$airflight->icao);

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

        $this->info('Se han actualizado: '.$updated.' aviones de '.$airflightsCount.'.');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Comenzando a reparar y añadir características en aviones');
        $this->fixAirplaneFlagsAndCountries();
        $this->info('Termina de reparar y añadir características en aviones');

        return self::SUCCESS;
    }
}
