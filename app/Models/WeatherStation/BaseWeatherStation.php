<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseModel;
use App\Traits\HasTimestampScopes;
use Illuminate\Support\Facades\Cache;

use function array_key_exists;

/**
 * Class BaseWeatherStation
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation betweenDates(string $from, string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation lastDays(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation latestRecord()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseWeatherStation today()
 *
 * @mixin \Eloquent
 */
class BaseWeatherStation extends BaseModel
{
    use HasTimestampScopes;

    protected $fillable = [
        'hardware_device_id',
        'value',
        'created_at',
    ];

    /**
     * Estas tablas NO tienen columna `updated_at`.
     *
     * Son series temporales: una lectura no se corrige, se añade otra. Las
     * migraciones de `meteorology_*` sólo crean `created_at`.
     *
     * Declararlo así es lo que entiende Eloquent. Antes se resolvía
     * sobrescribiendo `setUpdatedAt()` con un cuerpo vacío, y eso **no
     * bastaba**: `Builder::addUpdatedAtColumn()` no llama a ese método, mira
     * `getUpdatedAtColumn()`. Como seguía devolviendo `'updated_at'`, cualquier
     * `update()` de Eloquent sobre estas tablas montaba la columna en el SQL y
     * reventaba:
     *
     *     SQLSTATE[42703]: Undefined column: column "updated_at" of relation
     *     "meteorology_temperature" does not exist
     *
     * No había dado la cara porque la ingesta escribe con `insert()` del query
     * builder y nadie llamaba a `update()`. Con `UPDATED_AT = null`, Eloquent
     * deja de añadir la columna en `update()`, en `touch()` y al guardar el
     * modelo, que era la intención desde el principio.
     *
     * Salió al retirar del baseline de PHPStan las entradas de este fichero:
     * el aviso «`setUpdatedAt()` should return $this but return statement is
     * missing» llevaba silenciado todo este tiempo describiendo el síntoma.
     * Es el criterio de D14 aplicado.
     */
    const UPDATED_AT = null;

    /**
     * Devuelve los resultados para una página.
     *
     * @param  number  $size  Tamaño de cada página
     * @param  number  $page  Página a la que buscar.
     * @return array
     */
    public static function getTableRowsByPage($size, $page, $columns,
        $orderBy, $orderDirection = 'ASC')
    {
        return self::select($columns)
            ->offset(($page * $size) - $size)
            ->limit($size)
            ->orderBy($orderBy, $orderDirection)
            ->get()
            ->toArray();
    }

    /**
     * Devuelve un array con todos los atributos para un modelo instanciado
     *
     * @return array
     */
    public function getAllAttributes()
    {
        $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        // $columns = \Schema::getColumnListing($this->table);
        // but it's safer to just get the fillable fields

        $attributes = $this->getAttributes();

        foreach ($columns as $column) {
            if (! array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }

        return $attributes;
    }

    /*
    |--------------------------------------------------------------------------
    | `averageLast()` y `prepareApiResponse()` — retirados el 2026-09-02
    |--------------------------------------------------------------------------
    |
    | Devolvían el valor actual del sensor más un histórico de medias por hora,
    | y **no los llamaba nadie**: ni la API V2, ni las vistas Blade, ni un
    | comando, ni un test. Se comprobó sobre `app/`, `routes/`, `resources/`,
    | `database/` y `tests/`.
    |
    | Se retiran en vez de dejarlos porque no eran código inerte, eran código
    | con dos fallos dentro esperando a que alguien los llamara (auditoría
    | AR-E05):
    |
    |  1. `averageLast()` terminaba en `return $rest->toArray();` sobre el
    |     resultado de un `first()` metido en `Cache::remember()`. Una estación
    |     sin lecturas en el rango —un corte de luz, un cacharro
    |     reiniciándose— devolvía `null` y eso era un 500. No es el caso raro,
    |     es el normal.
    |
    |  2. Construía el SELECT interpolando el nombre de columna en SQL crudo:
    |     `$query->raw('ROUND( AVG('.$field.')::numeric, 1 ) as '.$field)`.
    |     `$field` salía de `$this->apiFields`, una propiedad del modelo, así
    |     que **no era explotable desde la petición**; pero es el patrón que no
    |     conviene dejar escrito, porque el día que alguien lo reutilice con un
    |     campo que venga de la request, la inyección está servida.
    |
    | Si vuelve a hacer falta el histórico por horas, se escribe de nuevo con el
    | `null` contemplado y sin `raw()` interpolado. Lo que hoy sirve datos
    | formateados de una estación es `WeatherStationService::getStationReadings()`,
    | que es lo que usa la API.
    */
}
