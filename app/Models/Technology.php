<?php

namespace App\Models;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Policies\TechnologyPolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int|null $image_id Relación con la imagen asociada
 * @property string $name Nombre de la tecnología.
 * @property string $slug Slug para el URL.
 * @property string|null $description Descripción breve de esta tecnología.
 * @property string $color Código Hexadecimal del color.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read \App\Models\File|null $image
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technology whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Technology extends BaseAbstractModelWithTableCrud
{
    use ImageTrait;

    protected $table = 'technologies';

    protected $fillable = ['name', 'slug', 'description', 'color'];

    /**
     * Asocia con la imagen de la tecnología.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    public static function getModuleName(): string
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
        return TechnologyPolicy::class;
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
            'color' => 'nullable|string|max:255',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
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
     * @return string[][]
     */
    public static function getTableCellsInfo(): array
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
