<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\TemperatureUpdateEvent;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class Temperature
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value
 * @property Carbon|null $created_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Temperature whereValue($value)
 *
 * @mixin \Eloquent
 */
class Temperature extends BaseWeatherStation
{
    use Notifiable;

    protected $table = 'meteorology_temperature';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'temperature';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Temperatura';

    public static function getModuleName(): string
    {
        return 'temperature';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Temperatura',
            'plural' => 'Temperatura',
            'add' => 'Agregar Temperatura',
            'edit' => 'Editar Temperatura',
            'delete' => 'Eliminar Temperatura',
        ];
    }

    protected $dispatchesEvents = [
        'created' => TemperatureUpdateEvent::class,
    ];

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'value' => 'Valor ºC',
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
