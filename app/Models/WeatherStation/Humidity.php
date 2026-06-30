<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\HumidityUpdateEvent;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class Humidity
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value
 * @property Carbon|null $created_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Humidity whereValue($value)
 *
 * @mixin \Eloquent
 */
class Humidity extends BaseWeatherStation
{
    use Notifiable;

    protected $table = 'meteorology_humidity';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'humidity';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Humedad';

    public static function getModuleName(): string
    {
        return 'humidity';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Humedad',
            'plural' => 'Humedades',
            'add' => 'Agregar Humedad',
            'edit' => 'Editar Humedad',
            'delete' => 'Eliminar Humedad',
        ];
    }

    protected $dispatchesEvents = [
        'created' => HumidityUpdateEvent::class,
    ];

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'value' => 'Valor %',
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
