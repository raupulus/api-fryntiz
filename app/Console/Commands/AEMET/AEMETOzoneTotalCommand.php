<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETOzoneTotal;
use Illuminate\Console\Command;

/**
 * Ozono total en superficie. Publicación diaria (`red/especial/ozono`).
 *
 * Descarga el CSV diario de la red especial de espectrofotómetros Brewer de AEMET
 * y persiste únicamente la estación más cercana a Chipiona: Moguer (El Arenosillo,
 * indicativo 5860E, única en Andalucía), configurada en `config('aemet.ozone_station_code')`.
 *
 * No confundir con `aemet:ozone-profile`: ese pide el perfil vertical de una
 * ozonosonda, un producto distinto que sólo se publica cada 7 días. Este es
 * el que el nombre "ozono" venía prometiendo desde siempre y nunca se había
 * implementado — ver docs/future/archived/revisar-aemet.md.
 *
 * ⚠️ NOTA FUTURO: En el futuro se prevé simplificar la tabla `meteorology_aemet_ozone_total`
 * eliminando las columnas de estación (`station_name`, `station_code`) e id autoincremental,
 * ya que la estación es fija para esta ubicación. Por el momento se mantienen en el modelo y
 * migración para compatibilidad. Ver docs/future/aemet-simplificar-ozono-total.md.
 */
class AEMETOzoneTotalCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:ozone-total';

    protected $description = 'Ozono total en superficie';

    public function handle(): int
    {
        $this->info('AEMET · ozono total: comenzando.');

        $this->guardedSave('ozono_total', fn () => \AEMETHelper::getOzoneTotal(), [AEMETOzoneTotal::class, 'saveFromApi']);

        $this->info('AEMET · ozono total: terminado.');

        return self::SUCCESS;
    }
}
