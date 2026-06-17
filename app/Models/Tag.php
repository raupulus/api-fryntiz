<?php

declare(strict_types=1);

namespace App\Models;

use App;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

use function route;

/**
 * @property int $id
 * @property string $name Nombre de la etiqueta
 * @property string $slug Slug para el URL
 * @property string|null $description Descripción acerca de lo que contendrá esta etiqueta
 * @property string|null $icon Clase css para el icono
 * @property string $color Código Hexadecimal del color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Tag extends BaseAbstractModelWithTableCrud
{
    protected $table = 'tags';

    protected $fillable = ['name', 'slug', 'description', 'icon', 'color'];

    public static function getModuleName(): string
    {
        return 'tag';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Etiqueta',
            'plural' => 'Etiquetas',
            'add' => 'Agregar Etiqueta',
            'edit' => 'Editar Etiqueta',
            'delete' => 'Eliminar Etiqueta',
        ];
    }

    /**
     * Elimina de forma segura la instancia actual.
     */
    public function safeDelete(): bool
    {
        return (bool) $this->delete();
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     */
    protected static function getPolicy(): ?string
    {
        return App\Policies\TagPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     */
    public static function getFieldsValidation(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,{id}',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
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
     * @return string[][]
     */
    public static function getTableCellsInfo(): array
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
     */
    public static function getTableActionsInfo(): Collection
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
                'ajax' => true,
            ],
        ]);
    }
}
