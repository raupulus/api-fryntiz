<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETAdverseEvents;
use Illuminate\Console\Command;

/**
 * Avisos de fenómenos meteorológicos adversos.
 *
 * Es el único producto que justifica una cadencia corta: cuando hay aviso, AEMET
 * lo emite en momentos concretos (09:00, 11:30, 23:00 y 23:50) y conviene
 * recogerlo pronto. Cada media hora.
 */
class AEMETAdverseEventsCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:adverse-events';

    protected $description = 'Avisos de fenómenos meteorológicos adversos (CAP) de Andalucía';

    public function handle(): int
    {
        $this->info('AEMET · avisos adversos: comenzando.');

        // Aquí «no hay nada» es lo normal: la mayoría de los días no hay
        // temporal en Cádiz. Por eso el vacío no se registra como aviso; si se
        // hiciera, el log tendría 48 warnings diarios y nadie miraría el que sí
        // importa.
        $this->guardedSave(
            'avisos_cap',
            fn () => \AEMETHelper::getAvisosCap(),
            [AEMETAdverseEvents::class, 'saveFromApi'],
            emptyIsNormal: true
        );

        $this->info('AEMET · avisos adversos: terminado.');

        return self::SUCCESS;
    }
}
