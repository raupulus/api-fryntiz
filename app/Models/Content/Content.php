<?php

namespace App\Models\Content;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Models\Category;
use App\Models\File;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\User;
use App\Policies\ContentPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use function route;
use function url;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;
use \Illuminate\Database\Eloquent\Relations\BelongsToMany;
use \Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Content
 *
 * @package App\Models\Content
 */
class Content extends BaseAbstractModelWithTableCrud
{
    use HasFactory, ImageTrait;

    protected $table = 'contents';

    protected $fillable = [
        'author_id',
        'platform_id',
        'status_id',
        'type_id',
        'image_id',
        'title',
        'slug',
        'excerpt',
        'is_copyright_valid',

        'is_comment_enabled',
        'is_comment_anonymous',
        'is_featured',
        'is_visible',
        'is_visible_on_home',
        'is_visible_on_menu',
        'is_visible_on_footer',
        'is_visible_on_sidebar',
        'is_visible_on_search',
        'is_visible_on_archive',
        'is_visible_on_rss',
        'is_visible_on_sitemap',
        'is_visible_on_sitemap_news',

        'processed_at',
        'published_at',
        'programmated_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'processed_at',
        'published_at',
        'programmated_at',
    ];


    public static function getModuleName(): string
    {
        return 'content';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Contenido',
            'plural' => 'Contenidos',
            'add' => 'Agregar Contenido',
            'edit' => 'Editar Contenido',
            'delete' => 'Eliminar Contenido',
        ];
    }

    /**
     * Devuelve la relación con el autor/usuario que ha creado el contenido.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Devuelve la relación con el autor/usuario que ha creado el contenido.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->author();
    }

    /**
     * Devuelve la relación con el estado del contenido.
     *
     * @return BelongsTo
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ContentAvailableStatus::class, 'status_id', 'id');
    }

    /**
     * Devuelve la relación al tipo de contenido.
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ContentAvailableType::class, 'type_id', 'id');
    }

    /**
     * Relación con la tabla "files" que contiene la imagen principal.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /**
     * Relación con las páginas asociadas al contenido.
     *
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ContentPage::class, 'content_id', 'id');
    }

    /**
     * Relación con el contenido que el actual asocia a otros.
     *
     * @return BelongsToMany
     */
    public function contentsRelated(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_id', 'content_related_id')
            ->where('contents.platform_id', $this->platform_id);
    }

    /**
     * Relación con el contenido actual asociado a otros de cualquier
     * plataforma.
     *
     * @return BelongsToMany
     */
    public function contentsRelatedAllPlatforms(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_id', 'content_related_id');
    }

    /**
     * Relación con el contenido asociado al actual.
     *
     * @return BelongsToMany
     */
    public function contentsRelatedMe(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_related_id', 'content_id')
            ->where('contents.platform_id', $this->platform_id);
    }

    /**
     * Relación con el contenido asociado al actual para cualquier plataforma.
     *
     * @return BelongsToMany
     */
    public function contentsRelatedMeAllPlatforms(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_related_id', 'content_id');
    }

    /**
     * Relación con los colaboradores asociados al contenido.
     *
     * @return BelongsToMany
     */
    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'content_contributors', 'content_id', 'user_id');
    }

    /**
     * Relación con los colaboradores asociados al contenido.
     *
     * @return HasMany
     */
    public function contributorsJoin(): HasMany
    {
        return $this->hasMany(ContentContributor::class, 'content_id', 'id');
    }

    /**
     * Relación con las galerías asociadas.
     *
     * @return HasMany
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(ContentGallery::class, 'content_id', 'id');
    }

    /**
     * Relación al seo asociado.
     *
     * @return HasOne
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ContentSeo::class, 'content_id', 'id');
    }

    /**
     * Creo consulta personalizada para las categorías, NO ES UNA RELACIÓN
     *
     */
    public function getCategoriesAttribute()
    {
        return $this->categoriesQuery()->get();
    }

    /**
     * Prepara la consulta sin ejecutarla para las etiquetas asociadas.
     *
     * @param int|null $platformId Id de la plataforma
     */
    public function categoriesQuery(int $platformId = null)
    {
        $categoriesId = Category::select('categories.id')
            ->leftJoin('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->leftJoin('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id')
            //->leftJoin('contents', 'contents.platform_id', '=','content_categories.content_id')
            ->where('content_categories.content_id', $this->id)
            ->where('platform_categories.platform_id', $platformId ?? $this->platform_id)
            ->groupBy('categories.id')
            ->get();

        return Category::whereIn('id', $categoriesId);
    }

    /**
     * Relación con las categorías asociadas.
     *
     * @return HasMany
     */
    public function categoriesJoin(): HasMany
    {
        return $this->hasMany(ContentCategory::class, 'content_id', 'id');
    }

    /**
     * Creo consulta personalizada para las etiquetas, NO ES UNA RELACIÓN
     *
     */
    public function getTagsAttribute()
    {
        return $this->tagsQuery()->get();
    }

    /**
     * Prepara la consulta sin ejecutarla para las etiquetas asociadas.
     *
     * @param int|null $platformId Id de la plataforma
     */
    public function tagsQuery(int $platformId = null)
    {
        $tagsId = Tag::select('tags.id')
            ->leftJoin('platform_tags', 'platform_tags.tag_id', '=',
                'tags.id')
            ->leftJoin('content_tags', 'content_tags.platform_tag_id', '=', 'platform_tags.id')
            //->leftJoin('contents', 'contents.platform_id', '=', 'content_categories.content_id')
            ->where('content_tags.content_id', $this->id)
            ->where('platform_tags.platform_id', $platformId ?? $this->platform_id)
            ->groupBy('tags.id')
            ->get();

        return Tag::whereIn('id', $tagsId);
    }

    /**
     * Relación con las etiquetas asociadas.
     *
     * @return HasMany
     */
    public function tagsJoin(): HasMany
    {
        return $this->hasMany(ContentTag::class, 'content_id', 'id');
    }

    /**
     * Relación con las etiquetas asociadas a través de la tabla de join.
     *
     * @return BelongsToMany
     */
    public function tagsPlatform(): BelongsToMany
    {
        return $this->belongsToMany(PlatformTag::class, 'content_tags', 'content_id', 'platform_tag_id');
    }

    /**
     * Relación con la plataforma asociada al contenido
     *
     * @return BelongsTo
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id', 'id');
    }

    /**
     * Devuelve la ruta hacia la imagen asociada.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        return $this->image ? $this->image->url : File::urlDefaultImage('large');
    }

    /**
     * Devuelve el thumbnail de la imagen asociada.
     *
     * @param $size
     *
     * @return mixed
     */
    public function urlThumbnail($size = 'medium')
    {
        if ($this->image) {
            return $this->image->thumbnail($size);
        }

        return File::urlDefaultImage($size);
    }

    /**
     * Devuelve la url para la previsualización del contenido
     * editándose, basándose en el útimo guardado.
     * Útil para previsualizar borradores principalmente.
     * TODO: Por implementar una vez se llegue a esta parte.
     */
    public function getUrlPreviewAttribute()
    {
        return url('TEMPORAL/URL/PAGINA/TMP/' . $this->slug);
    }

    /**
     * Devuelve la url para ver un contenido publicado.
     * Los administradores, propietario y colaboradores también pueden ver
     * borradores.
     * TODO: Por implementar una vez se llegue a esta parte.
     */
    public function getUrlAttribute()
    {
        return url('TEMPORAL/URL/PAGINA/' . $this->slug);
    }

    /**
     * Devuelve la url para editar un contenido.
     *
     * @return string Devuelve una cadena con la ruta para editar este
     *                contenido.
     */
    public function getUrlEditAttribute()
    {
        return route('panel.content.edit', ['content' => $this->id]);
    }

    /**
     * Almacena los contribuidores del contenido, previamente borrará los
     * existentes si los hubiera.
     *
     * @param array $contributors Es un array con los ids de los usuarios.
     *
     * @return void
     */
    public function saveContributors(Array $contributors)
    {

        $contributors = array_unique(array_filter($contributors));

        $this->contributorsJoin()->delete();

        foreach ($contributors as $contributor) {
            $this->contributorsJoin()->create([
                'user_id' => $contributor,
                'content_id' => $this->id,
            ]);
        }
    }

    /**
     * Almacena las etiquetas asociadas al contenido.
     *
     * @param array $tags Es un array con los ids de las etiquetas.
     *
     * @return void
     */
    public function saveTags(Array $tags)
    {

        ## Limpio etiquetas vacías y duplicadas.
        $tags = array_unique(array_filter($tags));

        $platformTags = $this->platform->tags()
            ->whereIn('tag_id', $tags)
            ->pluck('tag_id')
            ->toArray();

        ## Almacena las etiquetas que aún no están asociadas a la plataforma.
        $platformTagsDiff = array_diff($tags, $platformTags);

        ## Creamos las etiquetas que no estén asociadas a la plataforma.
        foreach ($platformTagsDiff as $platformTag) {
            $this->platform->tags()->create([
                'tag_id' => $platformTag,
            ]);
        }

        ## Etiquetas ya asociadas al contenido
        $contentTags = $this->tagsJoin()
            ->pluck('platform_tag_id')
            ->toArray();


        ## Borramos las etiquetas que no estén en el array, ya no forma parte del contenido.
        foreach ($contentTags as $contentTag ) {
            if (!in_array($contentTag, $tags)) {
                $contentTag->delete();
            }
        }

        ## Vuelvo a buscar las etiquetas asociadas al contenido, ya que puede haber cambiado
        $platformTags = PlatformTag::select(['id', 'tag_id'])
            ->whereIn('tag_id', $tags)
            ->where('platform_id', $this->platform_id)
            ->get();

        ## Cada etiqueta que no esté asociada al contenido, la creamos.
        foreach ($tags as $tag) {
            if (in_array($tag, $contentTags)) {
                continue;
            }

            $platformTag = $platformTags->where('tag_id', $tag)->first();


            if (!$platformTag) {
                continue;
            }

            $this->tagsJoin()->create([
                'content_id' => $this->id,
                'platform_tag_id' => $platformTag->id,
            ]);
        }



    }

    /**
     * Almacena las categorías del contenido, previamente borrará los
     * existentes si los hubiera.
     *
     * @param array $categories Es un array con los ids de las categorías.
     *
     * @return void
     */
    public function saveCategories(Array $categories)
    {
        ## Limpio categorías vacías y duplicadas.
        $categories = array_unique(array_filter($categories));

        $platformCategories = $this->platform->categories()
            ->whereIn('category_id', $categories)
            ->pluck('category_id')
            ->toArray();

        ## Almacena las categorías que aún no están asociadas a la plataforma.
        $platformCategoriesDiff = array_diff($categories, $platformCategories);

        ## Creamos las categorías que no estén asociadas a la plataforma.
        foreach ($platformCategoriesDiff as $platformCategory) {
            $this->platform->categories()->create([
                'category_id' => $platformCategory,
            ]);
        }

        ## Categorías ya asociadas al contenido
        $contentCategories = $this->categoriesJoin()
            ->pluck('platform_category_id')
            ->toArray();


        ## Borramos las categorías que no estén en el array, ya no forma parte del contenido.
        foreach ($contentCategories as $contentCategory) {
            if (!in_array($contentCategory, $categories)) {
                $contentCategory->delete();
            }
        }

        ## Vuelvo a buscar las categorías asociadas al contenido, ya que puede haber cambiado
        $platformCategories = PlatformCategory::select(['id', 'category_id'])
            ->whereIn('category_id', $categories)
            ->where('platform_id', $this->platform_id)
            ->get();

        ## Cada categoría que no esté asociada al contenido, la creamos.
        foreach ($categories as $category) {
            if (in_array($category, $contentCategories)) {
                continue;
            }

            $platformCategory = $platformCategories->where('category_id', $category)->first();

            if (!$platformCategory) {
                continue;
            }

            $this->categoriesJoin()->create([
                'content_id' => $this->id,
                'platform_category_id' => $platformCategory->id,
            ]);
        }
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return ContentPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
     *
     * @return array
     */
    public static function getFieldsValidation(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,{id}',
            'excerpt' => 'nullable|string|max:255',
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
            'url_image' => 'Imagen',
            'title' => 'Título',
            'published_at' => 'Publicado',
            'slug' => 'Slug',
            'excerpt' => 'Resumen',
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
            'url_image' => [
                'type' => 'text',
            ],
            'title' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'published_at' => [
                'type' => 'datetime',
                'format' => 'd/m/Y',
            ],
            'slug' => [
                'type' => 'text',
            ],
            'excerpt' => [
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
