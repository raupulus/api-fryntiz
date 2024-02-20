<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\TemperatureUpdateEvent;
use App\Events\WeatherStationUpdateEvent;
use Illuminate\Notifications\Notifiable;

/**
 * Class Temperature
 *
 * @package App\Models\WeatherStation
 */
class Temperature extends BaseWheaterStation
{
    use Notifiable;

    protected $table = 'meteorology_temperature';


    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'temperature';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Temperatura';

    public static function  getModuleName(): string
    {
        return 'temperature';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Temperatura',
            'plural' => 'Temperatura',
            'add' => 'Agregar Temperatura',
            'edit' => 'Editar Temperatura',
            'delete' => 'Eliminar Temperatura',
        ];
    }

    protected $dispatchesEvents = [
        'created' => TemperatureUpdateEvent::class,
    ];






    /****************** Métodos para tablas dinámicas ******************/


    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'value' => 'Valor ºC',
            'created_at' => 'Instante',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    public static function getTableCellsInfo():array
    {
        return [
            'id' => [
                'type' => 'integer',
            ],
            'value' => [
                'type' => 'float',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
