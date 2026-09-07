<?php

declare(strict_types=1);

namespace App\Services\KeyCounter;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio encargado del procesamiento y registro de pulsaciones y eventos del teclado y ratón.
 */
class KeyCounterService
{
    /**
     * Almacena el número de pulsaciones del teclado y purga la caché relacionada.
     *
     * @param  array  $data  Datos de pulsaciones de teclado recibidas por el cliente.
     * @return Keyboard Modelo Keyboard guardado en base de datos.
     */
    public function storeKeyboard(array $data): Keyboard
    {
        $keyboard = Keyboard::create($data);

        // Invalidar cachés del frontend al recibir datos nuevos
        Cache::forget('keycounter:keyboard:summary');
        Cache::forget('keycounter:widgets');
        Cache::forget('keycounter:year_total:'.now()->year);

        return $keyboard;
    }

    /**
     * Almacena las estadísticas de uso del ratón y purga la caché asociada.
     *
     * @param  array  $data  Datos de movimiento, clicks, y scroll del ratón.
     * @return Mouse Modelo Mouse guardado en base de datos.
     */
    public function storeMouse(array $data): Mouse
    {
        $mouse = Mouse::create($data);

        // Invalidar cachés del frontend al recibir datos nuevos
        Cache::forget('keycounter:mouse:summary');
        Cache::forget('keycounter:widgets');

        return $mouse;
    }

    // Nota: Mouse no aporta pulsaciones al total anual de Keyboard
    // (`keycounter:year_total:*`), por lo que no necesita invalidar esa caché.

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
     * @param  CarbonImmutable  $desde  Inicio del periodo, incluido.
     * @param  CarbonImmutable  $hasta  Fin del periodo, incluido.
     * @return array<string, mixed>
     */
    public function summary(int $userId, int $deviceId, CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $acotar = static function ($query) use ($userId, $deviceId, $desde, $hasta) {
            return $query->where('user_id', $userId)
                ->where('hardware_device_id', $deviceId)
                ->whereBetween('created_at', [$desde, $hasta]);
        };

        $teclado = $acotar(Keyboard::query())
            ->selectRaw(
                'COALESCE(SUM(pulsations), 0) AS pulsations_total,'.
                'COALESCE(SUM(pulsations_special_keys), 0) AS pulsations_total_special_keys,'.
                'COALESCE(MAX(score), 0) AS combo_score,'.
                'COALESCE(MAX(pulsations), 0) AS pulsation_high,'.
                'COALESCE(SUM(duration), 0) AS duration_seconds,'.
                'COUNT(*) AS sessions'
            )
            ->first();

        $raton = $acotar(Mouse::query())
            ->selectRaw(
                'COALESCE(SUM(total_clicks), 0) AS clicks_total,'.
                'COALESCE(MAX(total_clicks), 0) AS clicks_high,'.
                'COALESCE(SUM(duration), 0) AS duration_seconds,'.
                'COUNT(*) AS sessions'
            )
            ->first();

        return [
            'pulsations_total' => (int) $teclado->pulsations_total,
            'pulsations_total_special_keys' => (int) $teclado->pulsations_total_special_keys,
            'combo_score' => (int) $teclado->combo_score,
            'pulsation_high' => (int) $teclado->pulsation_high,
            'sessions' => (int) $teclado->sessions,
            'duration_seconds' => (int) $teclado->duration_seconds,
            'mouse' => [
                'clicks_total' => (int) $raton->clicks_total,
                'clicks_high' => (int) $raton->clicks_high,
                'sessions' => (int) $raton->sessions,
                'duration_seconds' => (int) $raton->duration_seconds,
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
