<?php

namespace App\Models\KeyCounter;

use Carbon\Carbon;
use Carbon\Traits\Creator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function array_key_exists;
use function array_unique;
use function count;
use function implode;
use function json_encode;

/**
 * Class KeyCounter
 *
 * @package App\Models\Keycounter
 */
class BaseKeyCounter extends Model
{
    protected $fillable = [
        'start_at',
        'end_at',
        'duration',
        'pulsations',
        'pulsations_special_keys',
        'pulsation_average',
        'score',
        'weekday',
        'device_id',
        'device_name',
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
        $query->where('start_at', '!=', null)
            ->where('end_at', '!=', null)
            ->where('pulsations', '!=', null)
            ->where('pulsations_special_keys', '!=', null)
            ->where('pulsation_average', '!=', null)
            ->where('score', '!=', null)
            ->where('weekday', '!=', null)
            ->where('created_at', '!=', null)
            ->sortByDesc('created_at');
        return $query;
    }

    /**
     * Devuelve la consulta filtrada sin ejecutar para obtener elementos
     * seguros.
     *
     * By Raúl Caro
     *
     * @param array $filter Recibe una matriz con los tipos de filtros dentro:
     *                      where => ['campo' => 'condicion'],
     *                      orWhere => ['campo' => 'condicion'],
     *                      whereNull => ['campo'],
     *                      whereNotNull => ['campo'],
     *
     * @return mixed
     */
    public static function getAllFiltered($filter = [])
    {
        $model = self::whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('pulsations', '>=', 1);

        ## Proceso el filtro de condiciones obligatorias.
        if (isset($filter['where']) && count($filter['where'])) {
            foreach ($filter['where'] as $idx => $filtro) {
                if ($filtro != null) {
                    $model->where($idx, $filtro);
                }
            }
        }

        ## Proceso el filtro de condiciones para no null.
        if (isset($filter['whereNotNull']) && count($filter['whereNotNull'])) {
            foreach ($filter['whereNotNull'] as $ele) {
                $model->whereNotNull($ele);
            }
        }

        ## Proceso el filtro de condiciones para obligar campos null.
        if (isset($filter['whereNull']) && count($filter['whereNull'])) {
            foreach ($filter['whereNull'] as $ele) {
                $model->whereNull($ele);
            }
        }

        ## Proceso el filtro de condiciones opcionales
        if (isset($filter['orWhere']) && count($filter['orWhere'])) {
            $model->where(function ($query) use ($filter) {
                foreach ($filter['orWhere'] as $idx => $filtro) {
                    if ($filtro) {
                        $query->orWhere($idx, 'LIKE', '%'.$filtro.'%');
                    }
                }

                return $query;
            });
        }

        return $model;
    }

    /**
     * Devuelve estadísticas del mes y año para el modelo que representa;
     *
     * @return array
     */
    public static function statistics($month = null, $year = null)
    {
        $now = Carbon::now();
        $now->setDay(1);
        $now->setTime(0, 0);

        ## Establezco mes si se ha indicado.
        if ($month) {
            $now->setMonth($month);
        }

        ## Establezco año si se ha indicado.
        if ($year) {
            $now->setYear($year);
        }

        ## Inicio del mes.
        $start = (clone($now))->format('Y-m-d H:i:s');

        ## Final del mes.
        $end = clone($now);
        $end->addMonth();
        $end->subDay();
        $end->setTime(23, 59, 59);
        $end = $end->format('Y-m-d H:i:s');

        ## Obtengo la consulta con los datos filtrados.
        $count = self::getAllFiltered()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        ## Obtengo las estadísticas agrupadas por cada dispositivo
        $data = self::getAllFiltered()
            ->select([
                DB::Raw('count(id) as spurts'), # Cantidad de rachas
                DB::Raw('sum(duration) as duration'),
                DB::Raw('DATE(created_at) as day'),
                DB::Raw('sum(pulsations) as total_pulsations'),
                DB::Raw('sum(pulsations_special_keys) as total_pulsations_special_keys'),
                DB::Raw('sum(pulsation_average) as total_pulsation_average'),
                DB::Raw('sum(score) as total_score'),
                'device_id',
                'device_name'
            ])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('device_id', 'day', 'device_name')
            ->orderBy('day')
            ->get();

        ## Extraigo el id de todos los dispositivos para este mes.
        $devices_ids = array_unique($data->pluck('device_id')->toArray());

        $total_puntuations = $data->sum('total_pulsations');

        return [
            'devices_ids' => $devices_ids,  ## Ids de todos los dispositivos.
            'period_start' => $start,  ## Comienzo del periodo
            'period_end' => $end,  ## Final del periodo
            'period_count' => $count,  ## Total de registros/rachas este periodo
            'period_total_pulsations' => $total_puntuations,
             #'period_max_pulsations' => $day_max_puntuations,
            'data' => $data,  ## Los datos devueltos como resultado
        ];
    }

    public static function getStatisticsPreparedToGraphics($month = null, $year = null)
    {
        $keyboard_statistics = self::statistics($month, $year);
        $stats = $keyboard_statistics['data']->sortBy('day');
        $colors = ['#3e95cd', '#8e5ea2', '#007bff', '#e8c3b9', '#c45850',
            '#000000', '#00ff00', '#0000ff', '#3cba9f'];
        $days = array_unique($stats->pluck('day')->toArray());
        $devices = $keyboard_statistics['devices_ids'];

        $labels = [];
        $dataset = [];
        $datasetTMP = [];

        ## Array temporal de días con el valor de las pulsaciones por día de todos los dispositivos.
        $totalTMP = [];

        ## Recorro todos los días y genero por cada dispositivo un array con los datos.
        foreach ($days as $day) {
            $labels[] = (new Carbon($day))->format('d');

            foreach ($devices as $device) {

                // FIXME → Esto no puede quedar haciendo consultas así
                // TODO → Mejorar forma de preparar los datos y usar REDIS/CACHE

                $s = $stats->where('day', $day)->where('device_id', $device)->first();

                ## Compruebo que haya registro para este dispositivo este día o seteo 0.
                if ($s && isset($datasetTMP[$device])) {
                    if (!isset($datasetTMP[$device]['label'])) {
                        $datasetTMP[$device]['label'] = $s->device_name;
                    }

                    if (!isset($datasetTMP[$device]['borderColor'])) {
                        $datasetTMP[$device]['borderColor'] = $colors[$s->device_id];
                    }

                    if (!isset($datasetTMP[$device]['fill'])) {
                        $datasetTMP[$device]['fill'] = 'false';
                    }

                    $datasetTMP[$device]['data'][] = $s->total_pulsations;
                } else if ($s) {
                    $datasetTMP[$device] = [
                        'data' => [$s->total_pulsations],
                        'label' => $s->device_name,
                        'borderColor' => $colors[$s->device_id],
                        'fill' => 'false'
                    ];
                } else {
                    $datasetTMP[$device]['data'][] = 0;
                }

                ## Añado al array total el valor actual.
                if ($s) {
                    $totalTMP[$day] = isset($totalTMP[$day]) ? $totalTMP[$day] +
                        $s->total_pulsations :
                        $s->total_pulsations;
                }
            }
        }

        ## Añado un nuevo elemento que agrupe todos los dispositivos con el total por día.
        $total = [];
        foreach ($totalTMP as $t) {
            $total[] = $t;
        }

        $datasetTMP[0] = [
            'data' => $total,
            'label' => 'Total',
            'borderColor' => '#ff0000',
            'fill' => 'false'
        ];

        ## Añado todos los datos por días y dispositivos al array final.
        foreach ($datasetTMP as $d) {
            $dataset[] = $d;
        }

        ## Convierto los datos a string y json para luego extraerlos en javascript.
        $labelsString = implode(',', array_unique($labels));
        $datasetJson = json_encode($dataset);

        return [
            'keyboard_statistics' => $keyboard_statistics,
            'labelsString' => $labelsString,
            'datasetJson' => $datasetJson,
        ];
    }
}
