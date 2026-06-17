<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Events\WeatherStation\LightUpdateEvent;
use Illuminate\Support\Carbon;

/**
 * Class Light
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $lumens Lumens
 * @property numeric $lux lux
 * @property numeric|null $index Índice
 * @property numeric|null $uva Rayos UVA
 * @property numeric|null $uvb Rayos UVB
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereLumens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereLux($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereUva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Light whereUvb($value)
 *
 * @mixin \Eloquent
 */
class Light extends BaseWheaterStation
{
    protected $table = 'meteorology_light';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'lumens',
        'index',
        'lux',
        'uva',
        'uvb',
        'created_at',
    ];

    /**
     * @var string[] Campos que se pueden devolver por api.
     */
    public $apiFields = [
        'lumens',
        'index',
        'lux',
        'uva',
        'uvb',
    ];

    /**
     * @var string Nombre de la variable.
     */
    public $slug = 'light';

    /**
     * Nombre amigable para la representación del modelo.
     *
     * @var string
     */
    public $name = 'Luz';

    public static function getModuleName(): string
    {
        return 'light';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Luz',
            'plural' => 'Luz',
            'add' => 'Agregar Luz',
            'edit' => 'Editar Luz',
            'delete' => 'Eliminar Luz',
        ];
    }

    protected $dispatchesEvents = [
        'created' => LightUpdateEvent::class,
    ];

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('lumens')
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
            'lumens' => 'required|float',
            'index' => 'required|float',
            'uva' => 'required|float',
            'uvb' => 'required|float',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'lumens' => 'Lúmenes',
            'index' => 'Índice',
            'uva' => 'UVA',
            'uvb' => 'UVB',
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
            'lumens' => [
                'type' => 'float',
            ],
            'index' => [
                'type' => 'float',
            ],
            'uva' => [
                'type' => 'float',
            ],
            'uvb' => [
                'type' => 'float',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }
}
