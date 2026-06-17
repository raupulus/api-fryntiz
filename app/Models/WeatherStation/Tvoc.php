<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use Illuminate\Support\Carbon;

/**
 * Class Tvoc
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value Valor entre  0ppb y 1187ppb
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tvoc whereValue($value)
 *
 * @mixin \Eloquent
 */
class Tvoc extends BaseWheaterStation
{
    protected $table = 'meteorology_tvoc';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'tvoc';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire (tvoc)';

    public static function getModuleName(): string
    {
        return 'tvoc';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Calidad del aire (tvoc)',
            'plural' => 'Calidades del aire (tvoc)',
            'add' => 'Agregar Calidad del aire (tvoc)',
            'edit' => 'Editar Calidad del aire (tvoc)',
            'delete' => 'Eliminar Calidad del aire (tvoc)',
        ];
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'value' => 'Valor',
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
