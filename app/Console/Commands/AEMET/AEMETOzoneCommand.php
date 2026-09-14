<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETOzone;
use Illuminate\Console\Command;

/**
 * Perfil vertical de ozono (sondeo de ozonosonda en Madrid). Publicación cada
 * 7 días, con hasta 28 días de retraso observado (ver
 * docs/apis/aemet/LIMITACIONES.md).
 *
 * Hasta el 2026-09-14 este comando se llamaba `aemet:ozone` y su descripción
 * decía "Ozono en superficie. Publicación diaria.": pedía y guardaba esto
 * mismo, pero el nombre y la cadencia del scheduler describían un producto
 * distinto (`red/especial/ozono`, el ozono total diario) que nunca ha llegado
 * a implementarse. Verificado contra la API real el 2026-09-14: el sobre de
 * `red/especial/perfilozono/estacion/peninsula` traía datos fechados 5 días
 * atrás, consistente con lo documentado.
 */
class AEMETOzoneCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:ozone-profile';

    protected $description = 'Perfil vertical de ozono (sondeo)';

    public function handle(): int
    {
        $this->info('AEMET · perfil de ozono: comenzando.');

        $this->guardedSave('ozono', fn () => \AEMETHelper::getOzone(), [AEMETOzone::class, 'saveFromApi']);

        $this->info('AEMET · perfil de ozono: terminado.');

        return self::SUCCESS;
    }
}
