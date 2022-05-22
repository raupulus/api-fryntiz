<?php

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use function array_keys;
use function asset;
use function response;
use function route;

class Tag extends BaseModel
{
    use HasFactory;


    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'id' => 'ID',
            'name' => 'Nombre',
            'slug' => 'Slug',
            'description' => 'Descripción',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    public static function getTableCellsInfo()
    {
        return [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'slug' => [
                'type' => 'text',
            ],
            'description' => [
                'type' => 'text',
            ],

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
            ->get();
    }


    /**
     * Devuelve los datos preparados para una tabla.
     *
     * @param int    $page La página a devolver.
     * @param int    $size Cantidad de elementos por página.
     * @param string $orderBy Campo sobre el que se ordena.
     * @param string $orderDirection Dirección al ordenar (ASC|DESC)
     *
     * @return array
     */
    public static function getTableQuery(int $page = 1,
                                         int $size = 10,
                                         string $orderBy = 'created_at',
                                         string $orderDirection = 'DESC')
    {
        $tableHeads = self::getTableHeads($page);
        $columns = array_keys($tableHeads);
        $tableRows = self::getTableRowsByPage($size, $page, $columns, $orderBy, $orderDirection);
        $totalElements = self::count();

        $cellsInfo = self::getTableCellsInfo();

        return [
            'heads' => $tableHeads,
            'rows' => $tableRows,
            'currentPage' => $page,
            'totalElements' => $totalElements,
            'size' => $size,
            'cellsInfo' => $cellsInfo,
        ];
    }

    /**
     * Devuelve las rutas de acciones
     *
     */
    public static function getTableActionsInfo()
    {
        // TODO Crear policies para devolver solo acciones permitidas ahora.

        return collect([
            [
                'type' => 'update',
                'name' => 'Editar',
                'url' => route('dashboard.tag.ajax.table.get'),
                'method' => 'PUT'
            ],
            [
                'type' => 'delete',
                'name' => 'Eliminar',
                'url' => '#',
                'method' => 'PUT'
            ]
        ]);
    }
}
