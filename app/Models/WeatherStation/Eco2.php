<?php

namespace App\Models\WeatherStation;

/**
 * Class Eco2
 *
 * @package App\Models\WeatherStation
 */
class Eco2 extends BaseWheaterStation
{
    protected $table = 'meteorology_eco2';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'eco2';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire (eCO2)';

    public static function  getModuleName(): string
    {
        return 'eco2';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Calidad del aire (eCO2)',
            'plural' => 'Calidades del aire (eCO2)',
            'add' => 'Agregar Calidad del aire (eCO2)',
            'edit' => 'Editar Calidad del aire (eCO2)',
            'delete' => 'Eliminar Calidad del aire (eCO2)',
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
            'value' => 'Valor ppm',
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
