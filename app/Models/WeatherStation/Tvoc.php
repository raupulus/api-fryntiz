<?php

namespace App\Models\WeatherStation;

/**
 * Class Tvoc
 *
 * @package App\Models\WeatherStation
 */
class Tvoc extends BaseWheaterStation
{
    protected $table = 'meteorology_tvoc';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'tvoc';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire (tvoc)';

    public static function  getModuleName(): string
    {
        return 'tvoc';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Calidad del aire (tvoc)',
            'plural' => 'Calidades del aire (tvoc)',
            'add' => 'Agregar Calidad del aire (tvoc)',
            'edit' => 'Editar Calidad del aire (tvoc)',
            'delete' => 'Eliminar Calidad del aire (tvoc)',
        ];
    }


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
            'value' => 'Valor',
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
