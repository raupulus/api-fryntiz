<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\AirQualityUpdateEvent;
use App\Events\WeatherStation\HumidityUpdateEvent;
use App\Events\WeatherStationUpdateEvent;
use Illuminate\Notifications\Notifiable;

/**
 * Class Humidity
 *
 * @package App\Models\WeatherStation
 */
class Humidity extends BaseWheaterStation
{
    use Notifiable;

    protected $table = 'meteorology_humidity';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'humidity';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Humedad';

    public static function  getModuleName(): string
    {
        return 'humidity';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Humedad',
            'plural' => 'Humedades',
            'add' => 'Agregar Humedad',
            'edit' => 'Editar Humedad',
            'delete' => 'Eliminar Humedad',
        ];
    }

    protected $dispatchesEvents = [
        'created' => HumidityUpdateEvent::class,
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
            'value' => 'Valor %',
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
