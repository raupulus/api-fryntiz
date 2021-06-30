<?php

namespace App\Models\WeatherStation;

/**
 * Class Lightning
 *
 * @package App\Models\WeatherStation
 */
class Lightning extends BaseWheaterStation
{
    protected $table = 'meteorology_lightning';

    protected $fillable = [
        'distance',
        'energy',
        'noise_floor',
        'created_at'
    ];

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'distance' => 'Distancia',
            'energy' => 'Energía',
            'noise_floor' => 'Reducción de Ruido',
            'created_at' => 'Instante'
        ];
    }
}
