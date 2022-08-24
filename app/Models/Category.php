<?php

namespace App\Models;

use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Policies\CategoryPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use function collect;
use function route;

/**
 * Class Category
 */
class Category extends BaseAbstractModelWithTableCrud
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = ['name', 'slug', 'description'];

    public static function  getModuleName(): string
    {
        return 'category';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Categoría',
            'plural' => 'Categorías',
            'add' => 'Agregar Categoría',
            'edit' => 'Editar Categoría',
            'delete' => 'Eliminar Categoría',
        ];
    }

    /**
     * Elimina de forma segura la instancia actual.
     *
     * @return bool
     */
    public function safeDelete(): bool
    {
        return (bool) $this->delete();
    }



    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return CategoryPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     *
     * @return array
     */
    public static function getFieldsValidation(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,{id}',
            'description' => 'nullable|string|max:255',
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
    public static function getTableCellsInfo():array
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
                'url' => route('dashboard.category.edit', '[id]'),
                'method' => 'GET',
                /*
                'params' => [

                ]
                */
            ],
            [
                'type' => 'delete',
                'name' => 'Eliminar',
                'url' => route('dashboard.category.destroy'),
                'method' => 'DELETE',
                'ajax' => true
            ]
        ]);
    }
}
