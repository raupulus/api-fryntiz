<?php

namespace App\Models\BaseModels;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use function array_keys;

/**
 * Class BaseAbstractModelWithTableCrud
 * Modelo mínimo con funciones comunes a todos los modelos y además plantea los
 * métodos para implementar cruds dinámico en tablas.
 *
 * @package App
 */
abstract class BaseAbstractModelWithTableCrud extends BaseModel
{
    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    abstract protected static function getPolicy(): string|null;

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array Ejemplo: ['id' => 'ID','name' => 'Nombre';
     */
    abstract public static function getTableHeads(): array;

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][] ['name' => ['type' => 'text','wrapper' => 'span','class' => 'text-weight-bold']]
     */
    abstract public static function getTableCellsInfo(): array;

    /**
     * Devuelve las rutas de acciones para la columna de botones.
     * El botón especial "Eliminar" con clave "delete" será dinámico.
     */
    abstract public static function getTableActionsInfo(): Collection;

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     *
     * @return array
     */
    abstract public static function getFieldsValidation(): array;


    /**
     * Devuelve una cadena con los datos de acción para la tabla en formato
     * JSON.
     *
     * @return string Cadena con formato JSON.
     */
    public static function getTableActionsInfoJson(): string
    {
        return self::getModel()::getTableActionsInfo()->toJson(JSON_UNESCAPED_UNICODE);
    }

    /**
     * Devuelve un array con todos los atributos fillables del modelo
     *
     * @return array
     */
    public static function getAttributesFillable(): array
    {
        return (new (self::getModel())())->fillable ?? [];
    }

    /**
     * Devuelve los resultados para una página respetando dirección y campos
     * para ordenar.
     *
     * @param array  $columns Columnas a mostrar.
     * @param string $orderBy Columna por la que se realizará el orden.
     * @param string $orderDirection Ordenación ascendente o descendente.
     * @param string|null $search Cadena de búsqueda.
     *
     * @return mixed
     */
    public static function prepareQueryFiltered(array $columns,
                                                string $orderBy = 'created_at',
                                                string $orderDirection = 'DESC',
                                                string|null $search = ''): Builder
    {
        $query = self::select($columns);

        if ($search) {
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    if ($column !== 'id') {
                        $q->orWhere($column, 'LIKE', '%' . $search . '%');
                    }
                }
            });
        }

        $query->orderBy($orderBy, $orderDirection);

        return $query;
    }

    /**
     * Devuelve los datos preparados para una tabla.
     *
     * @param int         $page           La página a devolver.
     * @param int         $size           Cantidad de elementos por página.
     * @param string|null $orderBy        Campo sobre el que se ordena.
     * @param string|null $orderDirection Dirección al ordenar (ASC|DESC)
     * @param string|null $search
     *
     * @return array
     */
    public static function getTableQuery(int         $page = 1,
                                         int         $size = 10,
                                         string|null $orderBy = 'created_at',
                                         string|null $orderDirection = 'DESC',
                                         string|null $search = ''): array
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
