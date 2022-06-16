<?php

namespace App\Models\BaseModels;

use Illuminate\Support\Collection;
use JetBrains\PhpStorm\ArrayShape;
use function array_keys;
use function collect;
use function route;

/**
 * Class BaseAbstractModelWithTableCrud
 * Modelo mínimo con funciones comunes a todos los modelos y además plantea los
 * métodos para implementar cruds dinámico en tablas.
 *
 * @package App
 */
abstract class BaseAbstractModelWithTableCrud extends BaseModel
{
    abstract protected static function getPolicy(): string;

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    abstract public static function getTableHeads(): array;

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    abstract public static function getTableCellsInfo(): array;

    /**
     * Devuelve las rutas de acciones para la columna de botones.
     * El botón especial "Eliminar" con clave "delete" será dinámico.
     *
     */
    abstract public static function getTableActionsInfo():Collection;

    /**
     * Devuelve los resultados para una página respetando dirección y campos
     * para ordenar.
     *
     * @param $columns
     * @param $orderBy
     * @param $orderDirection
     * @param $search
     *
     * @return mixed
     */
    public static function prepareQueryFiltered($columns,
                                                $orderBy, $orderDirection = 'DESC',
                                                $search = '')
    {
        $query = self::select($columns)->orderBy($orderBy, $orderDirection);

        if ($search) {
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    if ($column !== 'id') {
                        $q->orWhere($column, 'LIKE', '%' . $search . '%');
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Devuelve los datos preparados para una tabla.
     *
     * @param int         $page           La página a devolver.
     * @param int         $size           Cantidad de elementos por página.
     * @param string|null $orderBy        Campo sobre el que se ordena.
     * @param string|null $orderDirection Dirección al ordenar (ASC|DESC)
     *
     * @return array
     */
    public static function getTableQuery(int         $page = 1,
                                         int         $size = 10,
                                         string|null $orderBy = 'created_at',
                                         string|null $orderDirection = 'DESC',
                                         string|null $search = '')
    {
        $tableHeads = self::getModel()::getTableHeads($page);
        $columns = array_keys($tableHeads);

        $query = self::prepareQueryFiltered($columns, $orderBy, $orderDirection, $search);
        $totalElements = $query->count();
        $tableRows = $query->offset(($page * $size) - $size)->limit($size)->get();

        $cellsInfo = self::getModel()::getTableCellsInfo();

        return [
            'heads' => $tableHeads,
            'rows' => $tableRows,
            'currentPage' => $page,
            'totalElements' => $totalElements,
            'size' => $size,
            'cellsInfo' => $cellsInfo,
        ];
    }


}
