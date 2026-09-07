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
 * **Sale siempre con código 0**, también cuando toca renovar. El aviso lo da el
 * WARNING del log, que dice qué pasa y cuánto queda. Salir con 1 hacía que
 * `ScheduleRunCommand` lo tomara por una excepción y volcara su traza, así que
 * los quince días previos a la caducidad se llenaba el log de trazas inútiles
 * alrededor del único renglón que sirve. Una clave a punto de caducar es un
 * hallazgo del comando, no un fallo suyo; el código de salida queda para los
 * fallos de verdad, que son los que `onFailure` debe recoger.
 */
class AEMETCheckApiKeyCommand extends Command
{
    protected $signature = 'aemet:check-api-key';

    protected $description = 'Avisa si la clave de AEMET ha caducado o está a punto';

    public function handle(): int
    {
        $status = AemetApiKey::status();

        $this->line($status['message']);

        // Todo lo que no sea OK se avisa igual: caducada, a punto de caducar o
        // sin fecha de caducidad conocida. La rama de `NO_EXPIRY_DATE` estaba
        // aparte sólo porque salía con un código distinto; ya no lo hace.
        if ($status['status'] === AemetApiKey::OK) {
            return self::SUCCESS;
        }

        $this->warn($status['message']);
        Log::warning('AEMET: '.$status['message']);

        return self::SUCCESS;
    }
}
