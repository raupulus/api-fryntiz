<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETStationObservation;
use Illuminate\Console\Command;

/**
 * Observación convencional (dato real, no predicción) de las tres estaciones
 * AEMET cercanas a Chipiona (`config('aemet.stations')`). "Continuamente".
 *
 * Una estación cada una con su propio `guardedSave()`: si `getStationObservation()`
 * falla para una (p. ej. AEMET la deja de alimentar, como pasaba con el
 * `idema` equivocado de Rota antes de corregirlo), las otras dos se siguen
 * guardando igual.
 */
class AEMETStationObservationsCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:station-observations';

    protected $description = 'Observación convencional (dato real) de las estaciones AEMET cercanas a Chipiona';

    public function handle(): int
    {
        $this->info('AEMET · observación de estaciones: comenzando.');

        foreach (config('aemet.stations') as $zone => $idema) {
            $this->guardedSave(
                "station-observation:{$zone}",
                function () use ($idema, $zone) {
                    $rows = \AEMETHelper::getStationObservation($idema);

                    if ($rows === null) {
                        return null;
                    }

                    // `getStationObservation()` no conoce la zona: es una
                    // etiqueta nuestra, no algo que devuelva AEMET.
                    return array_map(
                        static fn (array $row) => $row + ['station_zone' => $zone],
                        $rows
                    );
                },
                [AEMETStationObservation::class, 'saveFromApi'],
            );
        }

        $this->info('AEMET · observación de estaciones: terminado.');

        return self::SUCCESS;
    }
}
