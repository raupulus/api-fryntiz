<?php

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\LightningUpdateEvent;

/**
 * Class Lightning
 *
 * @package App\Models\WeatherStation
 */
class Lightning extends BaseWheaterStation
{
    protected $table = 'meteorology_lightning';

    protected $fillable = [
        'hardware_device_id',
        'distance',
        'energy',
        'noise_floor',
        'created_at'
    ];

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'distance',
        'energy',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'lightning';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Rayos y Relámpagos';

    public static function  getModuleName(): string
    {
        return 'lightning';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Rayo/Relámpagos',
            'plural' => 'Rayos/Relámpagos',
            'add' => 'Agregar Rayos/Relámpagos',
            'edit' => 'Editar Rayos/Relámpagos',
            'delete' => 'Eliminar Rayos/Relámpagos',
        ];
    }

    protected $dispatchesEvents = [
        'created' => LightningUpdateEvent::class,
    ];










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
            'distance' => 'required|integer',
            'energy' => 'required|integer',
            'uva' => 'required|integer',
            'noise_floor' => 'required|bool',
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
            'distance' => 'Distancia (Km)',
            'energy' => 'Energía',
            'noise_floor' => 'Reducción de Ruido',
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
            'distance' => [
                'type' => 'integer',
            ],
            'energy' => [
                'type' => 'integer',
            ],
            'noise_floor' => [
                'type' => 'bool',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
