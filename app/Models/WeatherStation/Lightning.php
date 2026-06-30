<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\LightningUpdateEvent;
use Illuminate\Support\Carbon;

/**
 * Class Lightning
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property int $distance Distancia estimada de la caída del rayo
 * @property int $energy Energía detectada para detectar el rayo
 * @property int|null $noise_floor Ruido de fondo compensado para diferenciar el rayo
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereEnergy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereNoiseFloor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lightning whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Lightning extends BaseWeatherStation
{
    protected $table = 'meteorology_lightning';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'distance',
        'energy',
        'noise_floor',
        'created_at',
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

    public static function getModuleName(): string
    {
        return 'lightning';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Rayo/Relámpago',
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
            'distance' => 'required|integer',
            'energy' => 'required|integer',
            'uva' => 'required|integer',
            'noise_floor' => 'required|bool',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'distance' => 'Distancia (Km)',
            'energy' => 'Energía',
            'noise_floor' => 'Reducción de Ruido',
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
