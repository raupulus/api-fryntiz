<?php

namespace App\Console\Commands\AEMET\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Helper para los comandos AEMET: ejecuta una llamada al helper, valida que
 * la respuesta sea un array no vacío y delega en el callback de persistencia.
 *
 * Si el payload no cumple las garantías mínimas, registra un warning y omite
 * la persistencia, permitiendo que el resto del comando continúe.
 */
trait ValidatesAemetPayload
{
    /**
     * @param  string  $label       Etiqueta legible para los logs.
     * @param  callable $producer   Cierre que devuelve el payload (suele invocar a AEMETHelper).
     * @param  callable $persistor  Cierre o [Model::class, 'saveFromApi'] que persiste el payload.
     */
    protected function guardedSave(string $label, callable $producer, callable $persistor): void
    {
        try {
            $data = $producer();
        } catch (\Throwable $e) {
            Log::error("AEMET [{$label}]: excepción obteniendo el payload", [
                'message' => $e->getMessage(),
            ]);
            if (method_exists($this, 'warn')) {
                $this->warn("[{$label}] error al obtener datos. Ver logs.");
            }
            return;
        }

        if (! is_array($data) || empty($data)) {
            Log::warning("AEMET [{$label}]: payload vacío o no es un array.");
            if (method_exists($this, 'warn')) {
                $this->warn("[{$label}] payload vacío. Se omite la persistencia.");
            }
            return;
        }

        try {
            $persistor($data);
        } catch (\Throwable $e) {
            Log::error("AEMET [{$label}]: excepción persistiendo el payload", [
                'message' => $e->getMessage(),
            ]);
            if (method_exists($this, 'error')) {
                $this->error("[{$label}] error al guardar. Ver logs.");
            }
        }
    }
}
