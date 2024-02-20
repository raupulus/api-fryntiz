<?php

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use function array_key_exists;

/**
 * Class BaseWheaterStation
 *
 * @package App\Models\WeatherStation
 */
class BaseWheaterStation extends BaseAbstractModelWithTableCrud
{
    protected $fillable = [
        'hardware_device_id',
        'value',
        'created_at'
    ];

    /**
     * Sobreescribo la actualización del updated_at para no hacerle nada.
     *
     * @param mixed $value
     */
    public function setUpdatedAt($value)
    {
        //Do-nothing
    }

    public static function  getModuleName(): string
    {
        return 'weater_station';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Estación Meteorológica',
            'plural' => 'Estaciones Meteorológicas',
            'add' => 'Agregar Estación Meteorológica',
            'edit' => 'Editar Estación Meteorológica',
            'delete' => 'Eliminar Estación Meteorológica',
        ];
    }












    /**
     * Devuelve los resultados para una página.
     *
     * @param number $size Tamaño de cada página
     * @param number $page Página a la que buscar.
     *
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

        foreach ($columns as $column)
        {
            if (!array_key_exists($column, $attributes))
            {
                $attributes[$column] = null;
            }
        }
        return $attributes;
    }

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('value')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }

    /**
     * Obtiene el valor medio en las horas recibidas, esta consulta se hace en caché.
     *
     * El caché es de unos minutos, tiene sentido al ser una consulta
     * recurrente entre periodos con valores inmutables.
     *
     * @param int $hours
     *
     * @return mixed
     */
    public function averageLast(int $hours)
    {
        $slug = $this->slug;
        $fields = $this->apiFields;

        $now = Carbon::now()
            ->setMinute(0)
            ->setSecond(0)
            ->setMicrosecond(0);

        $lastHours = (clone($now))->subHours($hours);
        $initialHours = (clone($now))->subHours($hours - 1);

        $nowString = $initialHours->format('Y-m-d-H-i-s');
        $keyName = 'ws-' . $slug . '-hours-' . $hours .'_' . $nowString;

        $rest = Cache::remember($keyName, 600, function () use ($fields, $lastHours, $initialHours) {
            $query = self::where('created_at', '>=', $lastHours)
                ->where('created_at', '<=', $initialHours);

            foreach ($fields as $field) {
                $query->addSelect($query->raw('ROUND( AVG(' . $field . ')::numeric, 1 ) as ' . $field));
            }

            return $query->first();
        });

        return $rest->toArray();
    }

    /**
     * Preparamos y devolvemos los datos para la respuesta de la api.
     * Estos datos constan del valor actual para la lectura del registro en
     * el que estamos y además un histórico de las últimas 4 horas hacia atrás.
     *
     * @return \Illuminate\Support\Collection
     */
    public function prepareApiResponse()
    {
        $generic = [
            'name' => $this->name,
            'slug' => $this->slug,
        ];

        $now = Carbon::now()->setMinute(0)->setSecond(0)->setMicrosecond(0);

        $historical = collect([
            collect(array_merge($generic,
                $this->averageLast(1),
                [
                    'last_hours' => 1, // 1 - Resumen de la última hora
                    'created_at' => (clone($now))->subHours(1)->format('Y-m-d H:i:s'),
                ])),
            collect(array_merge($generic,
                $this->averageLast(2),

                [
                    'last_hours' => 2, // 2 - Resumen de las 2 últimas horas
                    'created_at' => (clone($now))->subHours(2)->format('Y-m-d H:i:s'),
                ])),
            collect(array_merge($generic,
                $this->averageLast(3),
                [
                    'last_hours' => 3, // 3 - Resumen de las 3 últimas horas
                    'created_at' => (clone($now))->subHours(3)->format('Y-m-d H:i:s'),
                ])),
            collect(array_merge($generic,
                $this->averageLast(4),
                [
                    'last_hours' => 4,// 4 - Resumen de las 4 últimas horas
                    'created_at' => (clone($now))->subHours(4)->format('Y-m-d H:i:s'),
                ])),
        ]);

        $datas = [];

        foreach ($this->apiFields as $field)
        {
            $datas[$field] = round($this->{$field}, 1);
        }

        $result = collect(array_merge($generic, $datas, [
            'created_at' => $this->created_at,
            'historical' => $historical,
        ]));

        // TODO: Esto es temporal, para mezclar dirección del viento con su
        // histórico dentro de la llamada para velocidad del viento.
        if ($result && $result['slug'] && $result['slug'] === 'wind') {
            $windDirection = WindDirection::whereNotNull('direction')
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($windDirection) {
                $result['direction'] = $windDirection->direction;
                $result['direction_grades'] = $windDirection->grades;
            }

            $windDirectionDatas = $windDirection->prepareApiResponse();

            if ($windDirectionDatas && $windDirectionDatas['historical'] &&
                count($windDirectionDatas['historical'])) {

                foreach ($windDirectionDatas['historical'] as $key =>  $historical) {

                    if (isset($result['historical'][$key])) {

                        $result['historical'][$key]['direction'] = WindDirection::getDirection($historical['grades']);
                        $result['historical'][$key]['direction_grades'] = $historical['grades'];
                    }
                }

            }
        }

        if ($result && $result['slug'] && $result['slug'] === 'air_quality') {

            $airQualityEco2 = Eco2::whereNotNull('value')
                ->orderByDesc('created_at')
                ->first();

            $airQualityTvoc = Tvoc::whereNotNull('value')
                ->orderByDesc('created_at')
                ->first();





            if ($airQualityEco2) {
                $result['eco2'] = $airQualityEco2->value;
            }

            if ($airQualityTvoc) {
                $result['tvoc'] = $airQualityTvoc->value;
            }

            $airQualityEco2Datas = $airQualityEco2->prepareApiResponse();
            $airQualityTvocDatas = $airQualityTvoc->prepareApiResponse();

            if ($airQualityEco2Datas && $airQualityEco2Datas['historical'] &&
                count($airQualityEco2Datas['historical'])) {

                foreach ($airQualityEco2Datas['historical'] as $key =>  $historical) {
                    if (isset($result['historical'][$key])) {
                        $result['historical'][$key]['eco2'] = $historical['value'];
                    }
                }

            }

            if ($airQualityTvocDatas && $airQualityTvocDatas['historical'] &&
                count($airQualityTvocDatas['historical'])) {

                foreach ($airQualityTvocDatas['historical'] as $key =>  $historical) {
                    if (isset($result['historical'][$key])) {
                        $result['historical'][$key]['tvoc'] = $historical['value'];
                    }
                }

            }
        }


        return $result;

    }











    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return null;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
     *
     * @return array
     */
    public static function getFieldsValidation(): array
    {
        return [
            'value' => 'required|integer',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
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
     * @return \string[][]
     */
    public static function getTableCellsInfo():array
    {
        return [
            'id' => [
                'type' => 'integer',
            ],
            'value' => [
                'type' => 'integer',
            ],
            'created_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],

        ];
    }

    /**
     * Devuelve las rutas de acciones
     *
     */
    public static function getTableActionsInfo():Collection
    {
        // TODO Crear policies para devolver solo acciones permitidas ahora.

        return collect([
            [
                'type' => 'update',
                'name' => 'Editar',
                'url' => route(self::getCrudRoutes()['edit'], '[id]'),
                'method' => 'GET',
                /*
                'params' => [

                ]
                */
            ],
            [
                'type' => 'delete',
                'name' => 'Eliminar',
                'url' => route(self::getCrudRoutes()['destroy']),
                'method' => 'DELETE',
                'ajax' => true
            ]
        ]);
    }
}
