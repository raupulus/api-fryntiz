<?php

namespace App\Models\WeatherStation;

/**
 * Class Rain
 *
 * @package App\Models\WeatherStation
 */
class Rain extends BaseWheaterStation
{
    protected $fillable = [
        'rain',
        'rain_intensity',
        'rain_month',
        'moisture',
        'created_at'
    ];

    protected $table = 'meteorology_rain';

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'rain' => 'Lluvia (mm)',
            'rain_intensity' => 'mm/h',
            'rain_month' => 'Mensual (mm)',
            'moisture' => 'Humedad (g/m3)',
            'created_at' => 'Instante'
        ];
    }

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();

        $query::whereNotNull('rain')
            ->orderBy('created_at', 'DESC')
            ->get();

        return $query;
    }
}
