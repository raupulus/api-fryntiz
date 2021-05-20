<?php

namespace App\Models\WeatherStation;

/**
 * Class AirQuality
 *
 * @package App\Models\WeatherStation
 */
class AirQuality extends BaseWheaterStation
{
    protected $fillable = [
        'gas_resistance',
        'air_quality',
        'created_at'
    ];

    protected $table = 'meteorology_air_quality';

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('gas_resistance')
            ->whereNotNull('air_quality')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }
}
