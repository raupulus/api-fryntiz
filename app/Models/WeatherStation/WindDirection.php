<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use Illuminate\Support\Carbon;

/**
 * Class WindDirection
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property int $grades Grados según puntos cardinales basados en resistencia, 0 es Norte, 90 es Este, 180 es Sur, 270 es Oeste
 * @property numeric|null $resistance Valor de la resistencia usado para calcular la posición del viento, valor entre 0 y 65535
 * @property string $direction Dirección del viento (N, S, E, O, NE, NO, SE, SO)
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereGrades($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WindDirection whereUserId($value)
 *
 * @mixin \Eloquent
 */
class WindDirection extends BaseWeatherStation
{
    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'resistance',
        'direction',
        'grades',
        'created_at',
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

    public static function getModuleName(): string
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
     */
    protected static function getPolicy(): ?string
    {
        return null;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
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
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'direction' => 'Dirección',
            'grades' => 'Grados',
            'created_at' => 'Instante',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return string[][]
     */
    public static function getTableCellsInfo(): array
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
