<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\AirQualityUpdateEvent;

/**
 * Class AirQuality
 *
 * @package App\Models\WeatherStation
 */
class AirQuality extends BaseWheaterStation
{
    protected $fillable = [
        'hardware_device_id',
        'gas_resistance',
        'air_quality',
        'created_at'
    ];

    protected $table = 'meteorology_air_quality';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'air_quality',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'air_quality';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire';

    protected $dispatchesEvents = [
        'created' => AirQualityUpdateEvent::class,
    ];

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'gas_resistance' => 'Resistencia',
            'air_quality' => 'Calidad del aire %',
            'created_at' => 'Instante'
        ];
    }

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
