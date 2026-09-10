<?php

declare(strict_types=1);

namespace App\Services\KeyCounter;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Carbon\CarbonImmutable;

/**
 * Servicio encargado del procesamiento y registro de pulsaciones y eventos del teclado y ratón.
 */
class KeyCounterService
{
    /**
     * Almacena el número de pulsaciones del teclado.
     *
     * **No toca la caché de la web, y es a propósito.** Hasta el 2026-09-10
     * esto olvidaba aquí el resumen, los widgets, el total del año y las
     * gráficas del mes en curso y del anterior. Sonaba razonable —«ha llegado
     * un dato, que se vea»— y hacía justo lo contrario de lo que se buscaba:
     *
     *  - **Se cargaba el retardo de privacidad.** La ventana dejaba de existir:
     *    la primera visita después de cada subida recalculaba con la racha
     *    recién llegada. Eso es tiempo real con otro nombre, y la página avisa
     *    de que no lo es.
     *  - **Se cargaba el rendimiento.** Cada ingesta dejaba la caché vacía, así
     *    que el cálculo entero se lo comía el siguiente visitante en vez del
     *    planificador.
     *  - Y en un almacén `file`, además, era una escritura por cada racha que
     *    sube un cacharro.
     *
     * Quien refresca es `keycounter:warm_cache --live`, cada hora. Una racha
     * nueva se ve como mucho una hora después: ver `KeyCounterCache`.
     *
     * @param  array  $data  Datos de pulsaciones de teclado recibidas por el cliente.
     * @return Keyboard Modelo Keyboard guardado en base de datos.
     */
    public function storeKeyboard(array $data): Keyboard
    {
        return Keyboard::create($data);
    }

    /**
     * Almacena las estadísticas de uso del ratón.
     *
     * Tampoco invalida nada, por lo mismo que `storeKeyboard()`.
     *
     * @param  array  $data  Datos de movimiento, clicks, y scroll del ratón.
     * @return Mouse Modelo Mouse guardado en base de datos.
     */
    public function storeMouse(array $data): Mouse
    {
        return Mouse::create($data);
    }

    /**
     * Resumen acumulado de un periodo, para que un cacharro que arranca
     * recupere lo que llevaba antes de apagarse.
     *
     * Un contador que se reinicia pierde su acumulado del día: lo pide aquí y
     * sigue sumando desde donde estaba. Por eso las sumas y los máximos van
     * juntos: las primeras para continuar el total, los segundos para no perder
     * el récord del periodo.
     *
     * @param  int  $userId  Dueño de los datos.
     * @param  int  $deviceId  El cacharro que pregunta. Uno, no varios.
     * @param  CarbonImmutable  $from  Inicio del periodo, incluido.
     * @param  CarbonImmutable  $to  Fin del periodo, incluido.
     * @return array<string, mixed>
     */
    public function summary(int $userId, int $deviceId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $scopeQuery = static function ($query) use ($userId, $deviceId, $from, $to) {
            return $query->where('user_id', $userId)
                ->where('hardware_device_id', $deviceId)
                ->whereBetween('created_at', [$from, $to]);
        };

        $keyboard = $scopeQuery(Keyboard::query())
            ->selectRaw(
                'COALESCE(SUM(pulsations), 0) AS pulsations_total,'.
                'COALESCE(SUM(pulsations_special_keys), 0) AS pulsations_total_special_keys,'.
                'COALESCE(MAX(score), 0) AS combo_score,'.
                'COALESCE(MAX(pulsations), 0) AS pulsation_high,'.
                'COALESCE(SUM(duration), 0) AS duration_seconds,'.
                'COUNT(*) AS sessions'
            )
            ->first();

        $mouse = $scopeQuery(Mouse::query())
            ->selectRaw(
                'COALESCE(SUM(total_clicks), 0) AS clicks_total,'.
                'COALESCE(MAX(total_clicks), 0) AS clicks_high,'.
                'COALESCE(SUM(duration), 0) AS duration_seconds,'.
                'COUNT(*) AS sessions'
            )
            ->first();

        return [
            'pulsations_total' => (int) $keyboard->pulsations_total,
            'pulsations_total_special_keys' => (int) $keyboard->pulsations_total_special_keys,
            'combo_score' => (int) $keyboard->combo_score,
            'pulsation_high' => (int) $keyboard->pulsation_high,
            'sessions' => (int) $keyboard->sessions,
            'duration_seconds' => (int) $keyboard->duration_seconds,
            'mouse' => [
                'clicks_total' => (int) $mouse->clicks_total,
                'clicks_high' => (int) $mouse->clicks_high,
                'sessions' => (int) $mouse->sessions,
                'duration_seconds' => (int) $mouse->duration_seconds,
            ],
        ];
    }

    /**
     * Calcula las estadísticas agregadas de teclado de un usuario en los últimos días.
     *
     * @param  int  $userId  ID del usuario.
     * @param  int  $days  Margen de días a contemplar (por defecto 30).
     * @return array Estadísticas que incluyen el total, promedio diario y número de registros.
     */
    public function getUserKeyboardStats(int $userId, int $days = 30): array
    {
        $records = Keyboard::forUser($userId)->lastDays($days)->get();

        return [
            'total_keystrokes' => $records->sum('pulsations') ?: 0,
            'average_per_day' => $days > 0 ? round(($records->sum('pulsations') ?: 0) / $days) : 0,
            'records_count' => $records->count(),
        ];
    }
}
