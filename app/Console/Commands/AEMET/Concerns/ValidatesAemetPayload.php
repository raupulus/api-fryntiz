<?php

declare(strict_types=1);

namespace App\Console\Commands\AEMET\Concerns;

use App\Support\WeatherStation\AemetApiKey;
use Illuminate\Support\Facades\Log;

/**
 * Helper para los comandos AEMET: ejecuta una llamada al helper, valida que
 * la respuesta sea un array no vacío y delega en el callback de persistencia.
 *
 * Si el payload no cumple las garantías mínimas, registra un warning y omite
 * la persistencia, permitiendo que el resto del comando continúe.
 *
 * Sólo lo usan comandos de consola, así que `warn()`, `info()` y `error()`
 * existen siempre: no hacen falta guardas con `method_exists()`.
 */
trait ValidatesAemetPayload
{
    /**
     * @param  string  $label  Etiqueta legible para los logs.
     * @param  callable  $producer  Cierre que devuelve el payload (suele invocar a AEMETHelper).
     * @param  callable  $persistor  Cierre o [Model::class, 'saveFromApi'] que persiste el payload.
     * @param  bool  $emptyIsNormal  Para los productos en los que «no hay nada»
     *                               es el estado habitual, como los avisos: sin
     *                               esto el log se llena de un warning cada
     *                               media hora los 300 días al año que no hay
     *                               temporal, y el aviso deja de significar nada.
     */
    protected function guardedSave(
        string $label,
        callable $producer,
        callable $persistor,
        bool $emptyIsNormal = false
    ): void {
        try {
            $data = $producer();
        } catch (\Throwable $e) {
            Log::error("AEMET [{$label}]: excepción obteniendo el payload", [
                'message' => $e->getMessage(),
            ]);
            $this->warn("[{$label}] error al obtener datos. Ver logs.");

            return;
        }

        if (is_array($data) && $data === [] && $emptyIsNormal) {
            $this->info("[{$label}] sin novedades.");

            return;
        }

        if (! is_array($data) || empty($data)) {
            // Un payload vacío tiene dos causas que en el log se ven IGUAL:
            // que hoy no haya datos, o que la clave haya caducado —AEMET
            // responde 200 con el cuerpo vacío, no un 401—. Se distingue aquí,
            // que es el único momento en que alguien lo va a leer.
            $key = AemetApiKey::status();
            $hint = $key['status'] === AemetApiKey::OK
                ? ''
                : ' '.$key['message'];

            Log::warning("AEMET [{$label}]: payload vacío o no es un array.".$hint, [
                'api_key_status' => $key['status'],
            ]);

            $this->warn("[{$label}] payload vacío. Se omite la persistencia.".$hint);

            return;
        }

        try {
            $persistor($data);
        } catch (\Throwable $e) {
            Log::error("AEMET [{$label}]: excepción persistiendo el payload", [
                'message' => $e->getMessage(),
            ]);
            $this->error("[{$label}] error al guardar. Ver logs.");
        }
    }
}
