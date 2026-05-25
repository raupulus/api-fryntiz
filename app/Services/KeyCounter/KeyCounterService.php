<?php

namespace App\Services\KeyCounter;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;

class KeyCounterService
{
    public function storeKeyboard(array $data): Keyboard
    {
        return Keyboard::create($data);
    }

    public function storeMouse(array $data): Mouse
    {
        return Mouse::create($data);
    }

    public function getUserKeyboardStats(int $userId, int $days = 30): array
    {
        $records = Keyboard::forUser($userId)->lastDays($days)->get();
        return [
            'total_keystrokes' => $records->sum('pulsaciones') ?: 0,
            'average_per_day' => $days > 0 ? round(($records->sum('pulsaciones') ?: 0) / $days) : 0,
            'records_count' => $records->count(),
        ];
    }
}
