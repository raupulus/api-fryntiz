<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\LightUpdateEvent;
use App\Events\WeatherStationUpdateEvent;

/**
 * Class Light
 *
 * @package App\Models\WeatherStation
 */
class Light extends BaseWheaterStation
{
    protected $table = 'meteorology_light';

    protected $fillable = [
        'lumens',
        'index',
        'uva',
        'uvb',
        'created_at'
    ];

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'lumens',
        'index',
        'uva',
        'uvb',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'light';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Luz';

    protected $dispatchesEvents = [
        'created' => LightUpdateEvent::class,
    ];


    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'lumens' => 'Lúmenes',
            'index' => 'Índice',
            'uva' => 'UVA',
            'uvb' => 'UVB',
            'created_at' => 'Instante'
        ];
    }

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('lumens')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }
}
