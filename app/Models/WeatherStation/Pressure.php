<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\PressureUpdateEvent;
use Illuminate\Support\Carbon;

/**
 * Class Pressure
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pressure whereValue($value)
 *
 * @mixin \Eloquent
 */
class Pressure extends BaseWheaterStation
{
    protected $table = 'meteorology_pressure';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'pressure';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Presión';

    public static function getModuleName(): string
    {
        return 'pressure';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Presión',
            'plural' => 'Presiones',
            'add' => 'Agregar Presión',
            'edit' => 'Editar Presión',
            'delete' => 'Eliminar Presión',
        ];
    }

    protected $dispatchesEvents = [
        'created' => PressureUpdateEvent::class,
    ];

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'value' => 'Valor hPa',
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
