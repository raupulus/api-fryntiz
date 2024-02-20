<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\PressureUpdateEvent;
use App\Events\WeatherStationUpdateEvent;

/**
 * Class Pressure
 *
 * @package App\Models\WeatherStation
 */
class Pressure extends BaseWheaterStation
{
    protected $table = 'meteorology_pressure';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'pressure';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Presión';

    public static function  getModuleName(): string
    {
        return 'pressure';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Presión',
            'plural' => 'Presiones',
            'add' => 'Agregar Presión',
            'edit' => 'Editar Presión',
            'delete' => 'Eliminar Presión',
        ];
    }

    protected $dispatchesEvents = [
        'created' => PressureUpdateEvent::class,
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
            'value' => 'Valor hPa',
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
