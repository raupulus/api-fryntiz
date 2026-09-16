<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload;
use App\Models\WeatherStation\AEMET\AEMETUvi;
use Illuminate\Console\Command;

/**
 * Índice de radiación ultravioleta máximo previsto, para
 * `config('aemet.uvi_city_code')` (Cádiz capital). Publicación diaria.
 */
class AEMETUviCommand extends Command
{
    use ValidatesAemetPayload;

    protected $signature = 'aemet:uvi';

    protected $description = 'Índice UV máximo previsto';

    public function handle(): int
    {
        $this->info('AEMET · UVI: comenzando.');

        $this->guardedSave('uvi', fn () => \AEMETHelper::getUvi(), [AEMETUvi::class, 'saveFromApi']);

        $this->info('AEMET · UVI: terminado.');

        return self::SUCCESS;
    }
}
