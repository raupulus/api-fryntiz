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


    protected $fillable = ['name', 'slug', 'description'];

    /**
     * Elimina de forma segura la instancia actual.
     *
     * @return bool
     */
    public function safeDelete()
    {
        return (bool) $this->delete();
    }

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
            $query->where(function ($q) use ($columns, $search){
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
     * @param int    $page La página a devolver.
     * @param int    $size Cantidad de elementos por página.
     * @param string|null $orderBy Campo sobre el que se ordena.
     * @param string|null $orderDirection Dirección al ordenar (ASC|DESC)
     *
     * @return array
     */
    public static function getTableQuery(int $page = 1,
                                         int $size = 10,
                                         string|null $orderBy = 'created_at',
                                         string|null $orderDirection = 'DESC',
                                         string|null $search = '')
    {
        $tableHeads = self::getTableHeads($page);
        $columns = array_keys($tableHeads);

        $query = self::prepareQueryFiltered($columns, $orderBy, $orderDirection, $search);
        $totalElements = $query->count();
        $tableRows = $query->offset(($page * $size) - $size)->limit($size)->get();

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
                'url' => route('dashboard.tag.edit', '[id]'),
                'method' => 'GET',
                /*
                'params' => [

                ]
                */
            ],
            [
                'type' => 'delete',
                'name' => 'Eliminar',
                'url' => route('dashboard.tag.destroy'),
                'method' => 'DELETE',
                'ajax' => true
            ]
        ]);
    }
}
