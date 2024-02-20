<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\AirQualityUpdateEvent;
use Illuminate\Support\Collection;

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

    public static function  getModuleName(): string
    {
        return 'air_quality';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Calidad del aire',
            'plural' => 'Calidads del aire',
            'add' => 'Agregar Calidad del aire',
            'edit' => 'Editar Calidad del aire',
            'delete' => 'Eliminar Calidad del aire',
        ];
    }

    protected $dispatchesEvents = [
        'created' => AirQualityUpdateEvent::class,
    ];


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




    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return null;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
     *
     * @return array
     */
    public static function getFieldsValidation(): array
    {
        return [
            'gas_resistance' => 'required|float',
            'air_quality' => 'required|float',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'gas_resistance' => 'Resistencia',
            'air_quality' => 'Calidad del aire %',
            'created_at' => 'Instante'
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return string[][]
     */
    public static function getTableCellsInfo():array
    {
        return [
            'id' => [
                'type' => 'integer',
            ],
            'gas_resistance' => [
                'type' => 'float',
            ],
            'air_quality' => [
                'type' => 'float',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }

}
