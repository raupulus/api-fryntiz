<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Policies\CategoryPolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

use function collect;
use function route;

/**
 * Class Category
 *
 * @property int $id
 * @property int|null $parent_id FK a la misma tabla para categorías padre
 * @property int|null $image_id FK a la imagen en la tabla files
 * @property int|null $priority Orden de prioridad de la categoría sobre otras, esto crea una ruta por ejemplo: /terminal/editores/vim
 * @property string $name Nombre de la categoría
 * @property string $slug Slug para el URL
 * @property string|null $description Descripción acerca de lo que contendrá esta etiqueta
 * @property string|null $icon Clase css para el icono
 * @property string $color Código Hexadecimal del color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 * @property-read Category|null $parentCategory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $subcategories
 * @property-read int|null $subcategories_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Category extends BaseAbstractModelWithTableCrud
{
    use ImageTrait;

    protected $table = 'categories';

    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'image_id', 'icon', 'color', 'priority'];

    public static function getModuleName(): string
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

    protected static function boot()
    {
        parent::boot();

        // Evento "saved": Se dispara después de ser guardado por primera vez y tras actualizarse
        static::saved(function ($model) {

            // # Actualiza el caché de categorías para todas las plataformas
            $platforms = Platform::all();
            foreach ($platforms as $platform) {
                $platform->cleanAllCache();
            }
        });
    }

    /**
     * Devuelve la categoría padre si la tuviera.
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /**
     * Devuelve todas las subcategorías asociadas a la categoría actual.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * Relación con la tabla "files" que contiene la imagen principal.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Elimina de forma segura la instancia actual.
     */
    public function safeDelete(): bool
    {
        // # Elimino la imagen asociada y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     */
    protected static function getPolicy(): ?string
    {
        return CategoryPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
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
