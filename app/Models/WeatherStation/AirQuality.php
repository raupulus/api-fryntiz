<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\AirQualityUpdateEvent;
use Illuminate\Support\Carbon;

/**
 * Class AirQuality
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $gas_resistance Valor de la resistencia del sensor
 * @property numeric $air_quality Resultado del algoritmo para calcular porcentaje de calidad del aire según resistencia, medida en frio y compensación por humedad
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality whereAirQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality whereGasResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AirQuality whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AirQuality extends BaseWeatherStation
{
    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'gas_resistance',
        'air_quality',
        'created_at',
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

    public static function getModuleName(): string
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
            'gas_resistance' => 'required|float',
            'air_quality' => 'required|float',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'gas_resistance' => 'Resistencia',
            'air_quality' => 'Calidad del aire %',
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
