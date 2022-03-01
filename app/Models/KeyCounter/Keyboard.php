<?php

namespace App\Models\KeyCounter;

/**
 * Class Keyboard
 *
 * @package App\Models\Keycounter
 */
class Keyboard extends BaseKeyCounter
{
    protected $table = 'keycounter_keyboard';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'start_at',
        'end_at',
        'duration',
        'pulsations',
        'pulsations_special_keys',
        'pulsation_average',
        'score',
        'weekday',
    ];
}
