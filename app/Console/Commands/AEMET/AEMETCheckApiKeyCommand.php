<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET;

use App\Support\WeatherStation\AemetApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Vigila la caducidad de la clave de AEMET.
 *
 * Existe porque el fallo es **silencioso**: la clave es un JWT que caduca a los
 * ~100 días y, cuando lo hace, AEMET no devuelve un 401 sino **200 con el
 * cuerpo vacío**. En los logs eso se ve exactamente igual que «hoy no hay
 * avisos», así que la integración se queda muda y nadie se entera hasta que
 * alguien echa de menos un dato semanas después.
 *
 * Sale con código 1 cuando hay que renovar, para que el planificador lo trate
 * como una tarea fallida y quede constancia.
 */
class AEMETCheckApiKeyCommand extends Command
{
    protected $signature = 'aemet:check-api-key';

    protected $description = 'Avisa si la clave de AEMET ha caducado o está a punto';

    public function handle(): int
    {
        $status = AemetApiKey::status();

        $this->line($status['message']);

        return match ($status['status']) {
            AemetApiKey::OK => self::SUCCESS,

            AemetApiKey::NO_EXPIRY_DATE => $this->warnAndExit($status['message'], self::SUCCESS),

            default => $this->warnAndExit($status['message'], self::FAILURE),
        };
    }

    private function warnAndExit(string $message, int $exitCode): int
    {
        $this->warn($message);
        Log::warning('AEMET: '.$message);

        return $exitCode;
    }
}
