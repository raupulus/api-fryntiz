<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETContamination;
use Illuminate\Console\Command;

/**
 * Contaminación atmosférica.
 *
 * AEMET declara periodicidad horaria para este producto, así que el planificador
 * lo llama cada hora. Antes se llamaba `aemet:update-every10m` y se ejecutaba
 * cada 10 minutos: cinco de cada seis llamadas no traían nada nuevo y se
 * comían la cuota del endpoint, que es de 40 peticiones y está ligada a la IP.
 */
class AEMETContaminationCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:contamination';

    protected $description = 'Contaminación atmosférica de la estación EMEP/VAG/CAMP';

    public function handle(): int
    {
        $this->info('AEMET · contaminación: comenzando.');

        $this->guardedSave('contaminacion', fn () => \AEMETHelper::getContamination(), [AEMETContamination::class, 'saveFromApi']);

        $this->info('AEMET · contaminación: terminado.');

        return self::SUCCESS;
    }
}
