<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\WindUpdateEvent;

/**
 * Class Wind
 *
 * @package App\Models\WeatherStation
 */
class Wind extends BaseWheaterStation
{
    protected $fillable = [
        'hardware_device_id',
        'speed',
        'average',
        'min',
        'max',
        'created_at'
    ];

    protected $table = 'meteorology_winter';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'speed',
        'average',
        'min',
        'max',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'wind';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Viento';

    protected $dispatchesEvents = [
        'created' => WindUpdateEvent::class,
    ];

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'speed' => 'Velocidad',
            'average' => 'Media',
            'min' => 'Mínimo',
            'max' => 'Máximo',
            'created_at' => 'Instante'
        ];
    }

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
