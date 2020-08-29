<?php

namespace App\Keycounter;

class Mouse extends Keyboard
{
    protected $table = 'keycounter_mouse';

    protected $fillable = [
        'start_at',
        'end_at',
        'duration',
        'clicks_left',
        'clicks_right',
        'clicks_middle',
        'total_clicks',
        'clicks_average',
        'weekday',
        'device_id',
        'device_name',
        'created_at'
    ];
}
