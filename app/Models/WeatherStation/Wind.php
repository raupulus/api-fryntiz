<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use Illuminate\Support\Carbon;

/**
 * Class Wind
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $speed Velocidad del viento m/s
 * @property numeric $average Velocidad promedio del viento m/s
 * @property numeric $min Velocidad mínima del viento m/s
 * @property numeric $max Velocidad máxima del viento m/s
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereAverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wind whereSpeed($value)
 *
 * @mixin \Eloquent
 */
class Wind extends BaseWeatherStation
{
    protected $fillable = [
        'hardware_device_id',
        'speed',
        'average',
        'min',
        'max',
        'created_at',
    ];

    protected $table = 'meteorology_winter';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'speed',
        'average',
        'min',
        'max',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'wind';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Viento';

    public static function getModuleName(): string
    {
        return 'wind';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Viento',
            'plural' => 'Viento',
            'add' => 'Agregar Viento',
            'edit' => 'Editar Viento',
            'delete' => 'Eliminar Viento',
        ];
    }

    /**
     * Convierte una velocidad almacenada en m/s (unidad nativa del sensor) a km/h.
     * Centraliza la conversión para reutilizarla en cualquier vista o respuesta
     * que deba mostrar la velocidad del viento en km/h.
     */
    public static function msToKmh(float|int|string|null $metersPerSecond): ?float
    {
        if ($metersPerSecond === null || ! is_numeric($metersPerSecond)) {
            return null;
        }

        return round((float) $metersPerSecond * 3.6, 2);
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
            'speed' => 'required|float',
            'average' => 'required|float',
            'min' => 'required|float',
            'max' => 'required|float',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'speed' => 'Actual km/h',
            'average' => 'Media km/h',
            'min' => 'Mínimo km/h',
            'max' => 'Máximo km/h',
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
            'speed' => [
                'type' => 'float',
            ],
            'average' => [
                'type' => 'float',
            ],
            'min' => [
                'type' => 'float',
            ], 'max' => [
                'type' => 'float',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
