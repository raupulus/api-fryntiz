<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use Illuminate\Support\Carbon;

/**
 * Class Eco2
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value Valor entre 400ppm y 8192ppm
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Eco2 whereValue($value)
 *
 * @mixin \Eloquent
 */
class Eco2 extends BaseWeatherStation
{
    protected $table = 'meteorology_eco2';

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'value',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'eco2';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Calidad del aire (eCO2)';

    public static function getModuleName(): string
    {
        return 'eco2';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Calidad del aire (eCO2)',
            'plural' => 'Calidades del aire (eCO2)',
            'add' => 'Agregar Calidad del aire (eCO2)',
            'edit' => 'Editar Calidad del aire (eCO2)',
            'delete' => 'Eliminar Calidad del aire (eCO2)',
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
            'value' => 'Valor ppm',
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
