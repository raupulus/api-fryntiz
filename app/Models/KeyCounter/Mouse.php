<?php

namespace App\Models\KeyCounter;

/**
 * Class Mouse
 *
 * @package App\Models\KeyCounter
 */
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

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query->where('start_at', '!=', null)
            ->where('end_at', '!=', null)
            ->where('pulsations', '!=', null)
            ->where('total_clicks', '>', 0)
            ->where('clicks_average', '>', 0)
            ->where('weekday', '!=', null)
            ->where('created_at', '!=', null)
            ->sortByDesc('created_at');
        return $query;
    }
}
