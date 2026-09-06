<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use Illuminate\Support\Carbon;

/**
 * Class Rain
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $rain Agua caída en el último periodo de tiempo (mm)
 * @property numeric|null $rain_intensity Intensidad de la lluvia en mm/h
 * @property numeric|null $rain_month Lluvia acumulada en el mes en mm
 * @property numeric $moisture Vapor de agua en el aire (g/m3)
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereMoisture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereRain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereRainIntensity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rain whereRainMonth($value)
 *
 * @mixin \Eloquent
 */
class Rain extends BaseWeatherStation
{
    protected $fillable = [
        'hardware_device_id',
        'rain',
        'rain_intensity',
        'rain_month',
        'moisture',
        'created_at',
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

    public static function getModuleName(): string
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
            'rain' => 'required|float',
            'rain_intensity' => 'required|float',
            'rain_month' => 'required|float',
            'moisture' => 'required|float',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'rain' => 'Lluvia (mm)',
            'rain_intensity' => 'mm/h',
            'rain_month' => 'Mensual (mm)',
            'moisture' => 'Humedad (g/m3)',
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
            'rain' => [
                'type' => 'float',
            ],
            'rain_intensity' => [
                'type' => 'float',
            ],
            'rain_month' => [
                'type' => 'float',
            ], 'moisture' => [
                'type' => 'float',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
