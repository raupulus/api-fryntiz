<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\RainUpdateEvent;

/**
 * Class Rain
 *
 * @package App\Models\WeatherStation
 */
class Rain extends BaseWheaterStation
{
    protected $fillable = [
        'hardware_device_id',
        'rain',
        'rain_intensity',
        'rain_month',
        'moisture',
        'created_at'
    ];

    protected $table = 'meteorology_rain';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'rain',
        'rain_intensity',
        'rain_month',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'rain';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Lluvia';

    public static function  getModuleName(): string
    {
        return 'rain';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Lluvia',
            'plural' => 'Lluvias',
            'add' => 'Agregar Lluvia',
            'edit' => 'Editar Lluvia',
            'delete' => 'Eliminar Lluvia',
        ];
    }

    protected $dispatchesEvents = [
        'created' => RainUpdateEvent::class,
    ];

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();

        $query::whereNotNull('rain')
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
            'rain' => 'required|float',
            'rain_intensity' => 'required|float',
            'rain_month' => 'required|float',
            'moisture' => 'required|float',
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
            'rain' => 'Lluvia (mm)',
            'rain_intensity' => 'mm/h',
            'rain_month' => 'Mensual (mm)',
            'moisture' => 'Humedad (g/m3)',
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
            'rain' => [
                'type' => 'float',
            ],
            'rain_intensity' => [
                'type' => 'float',
            ],
            'rain_month' => [
                'type' => 'float',
            ]
            ,'moisture' => [
                'type' => 'float',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
