<?php

namespace App\Models;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Policies\TechnologyPolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Technology extends BaseAbstractModelWithTableCrud
{
    use ImageTrait;

    protected $table = 'technologies';

    protected $fillable = ['name', 'slug', 'description', 'color'];


    /**
     * Asocia con la imagen de la tecnología.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }











    public static function  getModuleName(): string
    {
        return 'technology';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Tecnología',
            'plural' => 'Tecnologías',
            'add' => 'Agregar Tecnología',
            'edit' => 'Editar Tecnología',
            'delete' => 'Eliminar Tecnología',
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
        return TechnologyPolicy::class;
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
            'slug' => 'required|string|max:255|unique:tags,slug,{id}',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
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
            'image_id' => 'Imagen ID',
            'urlImage' => 'Imagen',
            'name' => 'Nombre',
            'slug' => 'Slug',
            'color' => 'Color',
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
            'image_id' => [
                'type' => 'hidden',
            ],
            'urlImage' => [
                'type' => 'image',
            ],
            'name' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'slug' => [
                'type' => 'text',
            ],
            'color' => [
                'type' => 'color',
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
