<?php

declare(strict_types=1);

namespace App\Services\KeyCounter;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
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
