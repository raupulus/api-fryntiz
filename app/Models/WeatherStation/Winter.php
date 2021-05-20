<?php

namespace App\Models\WeatherStation;

/**
 * Class Winter
 *
 * @package App\Models\WeatherStation
 */
class Winter extends BaseWheaterStation
{
    protected $fillable = [
        'speed',
        'average',
        'min',
        'max',
        'created_at'
    ];

    protected $table = 'meteorology_winter';

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('average')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }
}
