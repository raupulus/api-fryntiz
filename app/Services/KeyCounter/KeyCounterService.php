<?php

namespace App\Services\KeyCounter;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use Illuminate\Support\Facades\Cache;

class KeyCounterService
{
    public function storeKeyboard(array $data): Keyboard
    {
        $keyboard = Keyboard::create($data);

        // Invalidar cachés del frontend al recibir datos nuevos
        Cache::forget('keycounter:keyboard:summary');
        Cache::forget('keycounter:widgets');

        return $keyboard;
    }

    public function storeMouse(array $data): Mouse
    {
        $mouse = Mouse::create($data);

        // Invalidar cachés del frontend al recibir datos nuevos
        Cache::forget('keycounter:mouse:summary');
        Cache::forget('keycounter:widgets');

        return $mouse;
    }

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
