<?php

namespace App\Models\WeatherStation;

/**
 * Class WindDirection
 *
 * @package App\Models\WeatherStation
 */
class WindDirection extends BaseWheaterStation
{
    protected $fillable = [
        'hardware_device_id',
        'resistance',
        'direction',
        'grades',
        'created_at'
    ];

    protected $table = 'meteorology_wind_direction';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'grades',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'wind_direction';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Dirección del viento';

    public static function  getModuleName(): string
    {
        return 'wind_direction';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Dirección del viento',
            'plural' => 'Direcciones del viento',
            'add' => 'Agregar Dirección del viento',
            'edit' => 'Editar Dirección del viento',
            'delete' => 'Eliminar Dirección del viento',
        ];
    }

    // Esto se realiza dentro del modelo para la velocidad del viento.
    // Por ahora no se contempla tener un evento para la dirección del viento.
    /*
    protected $dispatchesEvents = [
        'created' => WeatherStationUpdateEvent::class,
    ];
    */


    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('direction')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }

    /**
     * Calcula la resistencia a 16bits según la dirección del viento.
     *
     * @param $grades
     *
     * @return float|int
     */
    public static function getResistance($grades)
    {
        $maxResistance = 65535; // 16bits

        return $maxResistance * ($grades / 360);
    }

    /**
     * Obtengo la dirección del viento según los grados.
     *
     * @param $grades
     *
     * @return string
     */
    public static function getDirection($grades)
    {
        if ($grades >= 0 && $grades < 22.5) {
            return 'N';
        } elseif ($grades >= 22.5 && $grades < 67.5) {
            return 'NE';
        } elseif ($grades >= 67.5 && $grades < 112.5) {
            return 'E';
        } elseif ($grades >= 112.5 && $grades < 157.5) {
            return 'SE';
        } elseif ($grades >= 157.5 && $grades < 202.5) {
            return 'S';
        } elseif ($grades >= 202.5 && $grades < 247.5) {
            return 'SW';
        } elseif ($grades >= 247.5 && $grades < 292.5) {
            return 'W';
        } elseif ($grades >= 292.5 && $grades < 337.5) {
            return 'NW';
        } elseif ($grades >= 337.5 && $grades <= 360) {
            return 'N';
        } else {
           return 'N/A';
        }
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
            'direction' => 'required|string',
            'grades' => 'required|integer',
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
            'direction' => 'Dirección',
            'grades' => 'Grados',
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
            'direction' => [
                'type' => 'string',
            ],
            'grades' => [
                'type' => 'integer',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
