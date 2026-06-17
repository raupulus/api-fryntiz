<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Models\Category;
use App\Models\ContentDailyView;
use App\Models\File;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Policies\ContentPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

use function route;
use function url;

/**
 * Class Content
 *
 * @property int $id
 * @property int|null $author_id FK al usuario propietario del post
 * @property int|null $platform_id FK a la plataforma que se asocia este contenido
 * @property int|null $status_id FK al estado en la tabla content_status
 * @property int|null $type_id FK al tipo de contenido en la tabla content_type
 * @property int|null $image_id FK a la imagen en la tabla files
 * @property string $title Título de la página
 * @property string $slug Slug para el URL
 * @property string|null $excerpt Descripción breve del contenido
 * @property bool|null $is_copyright_valid Indica si se ha comprobado que el contenido no contiene copyright. Si es null, no se ha comprobado
 * @property bool $is_active Indica si el contenido está activo
 * @property bool $is_comment_enabled Indica si los comentarios están habilitados
 * @property bool $is_comment_anonymous Indica si se permiten comentarios anónimos
 * @property bool $is_featured Indica si el contenido es destacado
 * @property bool $is_visible_on_home Indica si el contenido está visible en la página principal
 * @property bool $is_visible_on_menu Indica si el contenido está visible en el menú
 * @property bool $is_visible_on_footer Indica si el contenido está visible en el footer
 * @property bool $is_visible_on_sidebar Indica si el contenido está visible en el sidebar
 * @property bool $is_visible_on_search Indica si el contenido está visible en la búsqueda
 * @property bool $is_visible_on_archive Indica si el contenido está visible en el archivo
 * @property bool $is_visible_on_rss Indica si el contenido está visible en el RSS
 * @property bool $is_visible_on_sitemap Indica si el contenido está visible en el sitemap
 * @property bool $is_visible_on_sitemap_news Indica si el contenido está visible en el sitemap de noticias
 * @property Carbon|null $published_at Fecha de publicación del contenido
 * @property Carbon|null $scheduled_at Momento en la que está programada la publicación del contenido, deberá ser previamente visible. Si es null, no está programada y estará visible en cualquier momento
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read User|null $author
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentCategory> $categoriesJoin
 * @property-read int|null $categories_join_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contentsRelated
 * @property-read int|null $contents_related_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contentsRelatedAllPlatforms
 * @property-read int|null $contents_related_all_platforms_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contentsRelatedMe
 * @property-read int|null $contents_related_me_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contentsRelatedMeAllPlatforms
 * @property-read int|null $contents_related_me_all_platforms_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $contributors
 * @property-read int|null $contributors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentContributor> $contributorsJoin
 * @property-read int|null $contributors_join_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentDailyView> $dailyViews
 * @property-read int|null $daily_views_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentGallery> $galleries
 * @property-read int|null $galleries_count
 * @property-read mixed $categories
 * @property-read mixed $subcategories
 * @property-read mixed $tags
 * @property-read mixed $url
 * @property-read string $url_edit
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read mixed $url_preview
 * @property-read File|null $image
 * @property-read ContentMetadata|null $metadata
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentPage> $pages
 * @property-read int|null $pages_count
 * @property-read Platform|null $platform
 * @property-read ContentSeo|null $seo
 * @property-read ContentAvailableStatus|null $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentTag> $tagsJoin
 * @property-read int|null $tags_join_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlatformTag> $tagsPlatform
 * @property-read int|null $tags_platform_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Technology> $technologies
 * @property-read int|null $technologies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentTechnology> $technologiesJoin
 * @property-read int|null $technologies_join_count
 * @property-read ContentAvailableType|null $type
 * @property-read User|null $user
 *
 * @method static \Database\Factories\Content\ContentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Content featured()
 * @method static Builder<static>|Content forPlatform(int $platformId)
 * @method static Builder<static>|Content newModelQuery()
 * @method static Builder<static>|Content newQuery()
 * @method static Builder<static>|Content ofType(int $typeId)
 * @method static Builder<static>|Content published()
 * @method static Builder<static>|Content query()
 * @method static Builder<static>|Content scheduled()
 * @method static Builder<static>|Content whereAuthorId($value)
 * @method static Builder<static>|Content whereCreatedAt($value)
 * @method static Builder<static>|Content whereDeletedAt($value)
 * @method static Builder<static>|Content whereExcerpt($value)
 * @method static Builder<static>|Content whereId($value)
 * @method static Builder<static>|Content whereImageId($value)
 * @method static Builder<static>|Content whereIsActive($value)
 * @method static Builder<static>|Content whereIsCommentAnonymous($value)
 * @method static Builder<static>|Content whereIsCommentEnabled($value)
 * @method static Builder<static>|Content whereIsCopyrightValid($value)
 * @method static Builder<static>|Content whereIsFeatured($value)
 * @method static Builder<static>|Content whereIsVisibleOnArchive($value)
 * @method static Builder<static>|Content whereIsVisibleOnFooter($value)
 * @method static Builder<static>|Content whereIsVisibleOnHome($value)
 * @method static Builder<static>|Content whereIsVisibleOnMenu($value)
 * @method static Builder<static>|Content whereIsVisibleOnRss($value)
 * @method static Builder<static>|Content whereIsVisibleOnSearch($value)
 * @method static Builder<static>|Content whereIsVisibleOnSidebar($value)
 * @method static Builder<static>|Content whereIsVisibleOnSitemap($value)
 * @method static Builder<static>|Content whereIsVisibleOnSitemapNews($value)
 * @method static Builder<static>|Content wherePlatformId($value)
 * @method static Builder<static>|Content wherePublishedAt($value)
 * @method static Builder<static>|Content whereScheduledAt($value)
 * @method static Builder<static>|Content whereSlug($value)
 * @method static Builder<static>|Content whereStatusId($value)
 * @method static Builder<static>|Content whereTitle($value)
 * @method static Builder<static>|Content whereTypeId($value)
 * @method static Builder<static>|Content whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
        'is_active',
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
        'scheduled_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
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

    protected static function boot()
    {
        parent::boot();

        // Evento "saved": Se dispara después de ser guardado por primera vez y tras actualizarse
        static::saved(function ($model) {
            // $model->cleanAllCache(); // Es mejor hacerlo en store/update para tener la asociación de categorías
            // \Log::info('El modelo Platform ha disparado saved:', ['modelo' => $model]);

            $platform = $model->platform;

            if ($platform) {
                $platform->cleanAllCache();
            }
        });

        // Evento "updated": Solo se dispara cuando el modelo es actualizado
        static::updated(function ($model) {
            // $model->cleanAllCache();
            // \Log::info('El modelo Platform ha disparado updated:', ['modelo' => $model]);
        });
    }

    /**
     * Devuelve la relación con el autor/usuario que ha creado el contenido.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    /**
     * Devuelve la relación con el autor/usuario que ha creado el contenido.
     */
    public function user(): BelongsTo
    {
        return $this->author();
    }

    /**
     * Devuelve la relación con el estado del contenido.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ContentAvailableStatus::class, 'status_id', 'id');
    }

    /**
     * Devuelve la relación al tipo de contenido.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ContentAvailableType::class, 'type_id', 'id');
    }

    /**
     * Relación con la tabla "files" que contiene la imagen principal.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Relación con las páginas asociadas al contenido.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ContentPage::class, 'content_id', 'id')->orderBy('order');
    }

    /**
     * Vistas diarias del contenido.
     */
    public function dailyViews(): HasMany
    {
        return $this->hasMany(ContentDailyView::class, 'content_id', 'id');
    }

    /**
     * Relación con el contenido que el actual asocia a otros.
     */
    public function contentsRelated(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_id', 'content_related_id')
            ->where('contents.platform_id', $this->platform_id);
    }

    /**
     * Relación con el contenido actual asociado a otros de cualquier
     * plataforma.
     */
    public function contentsRelatedAllPlatforms(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_id', 'content_related_id');
    }

    /**
     * Relación con el contenido asociado al actual en la plataforma actual.
     */
    public function contentsRelatedMe(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_related_id', 'content_id')
            ->where('contents.platform_id', $this->platform_id);
    }

    /**
     * Relación con el contenido asociado al actual para cualquier plataforma.
     */
    public function contentsRelatedMeAllPlatforms(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_related_id', 'content_id');
    }

    /**
     * Relación con los colaboradores asociados al contenido.
     */
    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'content_contributors', 'content_id', 'user_id');
    }

    /**
     * Relación con los colaboradores asociados al contenido.
     */
    public function contributorsJoin(): HasMany
    {
        return $this->hasMany(ContentContributor::class, 'content_id', 'id');
    }

    /**
     * Relación con las tecnologías.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'content_technologies', 'content_id', 'technology_id');
    }

    /**
     * Relación con la tabla intermedia de las tecnologías.
     */
    public function technologiesJoin(): HasMany
    {
        return $this->hasMany(ContentTechnology::class, 'content_id', 'id');
    }

    /**
     * Relación con las galerías asociadas.
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(ContentGallery::class, 'content_id', 'id');
    }

    /**
     * Relación al seo asociado.
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ContentSeo::class, 'content_id', 'id');
    }

    /**
     * Relación a los metadatos asociados al contenido.
     */
    public function metadata(): HasOne
    {
        return $this->hasOne(ContentMetadata::class, 'content_id', 'id');
    }

    /**
     * Creo consulta personalizada para las categorías, NO ES UNA RELACIÓN
     */
    public function getCategoriesAttribute()
    {
        return $this->categoriesQuery()->get();
    }

    /**
     * Prepara la consulta sin ejecutarla para las etiquetas asociadas.
     *
     * @param  int|null  $platformId  Id de la plataforma
     */
    public function categoriesQuery(?int $platformId = null): Builder
    {
        $categoriesId = Category::select('categories.id')
            ->leftJoin('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->leftJoin('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id')
            // ->leftJoin('contents', 'contents.platform_id', '=','content_categories.content_id')
            ->where('content_categories.content_id', $this->id)
            ->where('platform_categories.platform_id', $platformId ?? $this->platform_id)
            ->groupBy('categories.id')
            ->whereNull('categories.parent_id')
            ->pluck('categories.id');

        return Category::whereIn('id', $categoriesId);
    }

    /**
     * Creo consulta personalizada para las subcategorías, NO ES UNA RELACIÓN
     */
    public function getSubcategoriesAttribute()
    {
        return $this->subcategoriesQuery()->select('categories.*')->get();
    }

    /**
     * Prepara la consulta sin ejecutarla para las etiquetas asociadas.
     *
     * @param  int|null  $platformId  Id de la plataforma
     */
    public function subcategoriesQuery(?int $platformId = null): Builder
    {
        $categoriesId = Category::select('categories.id')
            ->leftJoin('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->leftJoin('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id')
            // ->leftJoin('contents', 'contents.platform_id', '=','content_categories.content_id')
            ->where('content_categories.content_id', $this->id)
            ->where('platform_categories.platform_id', $platformId ?? $this->platform_id)
            ->groupBy('categories.id')
            ->whereNotNull('categories.parent_id')
            ->pluck('categories.id');

        // Falla al obtener las categorías, el leftJoin de platform_categories duplica las categorías

        /*
        dd($categoriesId, Category::whereIn('categories.id', $categoriesId)
            ->where('platform_categories.platform_id', $this->platform_id)
            ->leftJoin('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->leftJoin('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id')
            ->pluck('categories.id'));
        */

        return Category::whereIn('categories.id', $categoriesId)
            ->where('platform_categories.platform_id', $this->platform_id)
            ->leftJoin('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->leftJoin('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id');
    }

    /**
     * Relación con las categorías asociadas.
     */
    public function categoriesJoin(): HasMany
    {
        return $this->hasMany(ContentCategory::class, 'content_id', 'id');
    }

    /**
     * Creo consulta personalizada para las etiquetas, NO ES UNA RELACIÓN
     */
    public function getTagsAttribute()
    {
        return $this->tagsQuery()->get();
    }

    /**
     * Prepara la consulta sin ejecutarla para las etiquetas asociadas.
     *
     * @param  int|null  $platformId  Id de la plataforma
     */
    public function tagsQuery(?int $platformId = null)
    {
        $tagsId = Tag::select('tags.id')
            ->leftJoin('platform_tags', 'platform_tags.tag_id', '=',
                'tags.id')
            ->leftJoin('content_tags', 'content_tags.platform_tag_id', '=', 'platform_tags.id')
            // ->leftJoin('contents', 'contents.platform_id', '=', 'content_categories.content_id')
            ->where('content_tags.content_id', $this->id)
            ->where('platform_tags.platform_id', $platformId ?? $this->platform_id)
            ->groupBy('tags.id')
            ->get();

        return Tag::whereIn('id', $tagsId);
    }

    /**
     * Relación con las etiquetas asociadas.
     */
    public function tagsJoin(): HasMany
    {
        return $this->hasMany(ContentTag::class, 'content_id', 'id');
    }

    /**
     * Relación con las etiquetas asociadas a través de la tabla de join.
     */
    public function tagsPlatform(): BelongsToMany
    {
        return $this->belongsToMany(PlatformTag::class, 'content_tags', 'content_id', 'platform_tag_id');
    }

    /**
     * Relación con la plataforma asociada al contenido
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id', 'id');
    }

    /**
     * Devuelve la url para la previsualización del contenido
     * editándose, basándose en el útimo guardado.
     * Útil para previsualizar borradores principalmente.
     * TODO: Por implementar una vez se llegue a esta parte.
     */
    public function getUrlPreviewAttribute()
    {
        return url('TEMPORAL/URL/PAGINA/TMP/'.$this->slug);
    }

    /**
     * Devuelve la url para ver un contenido publicado.
     * Los administradores, propietario y colaboradores también pueden ver
     * borradores.
     * TODO: Por implementar una vez se llegue a esta parte.
     */
    public function getUrlAttribute()
    {
        return url('TEMPORAL/URL/PAGINA/'.$this->slug);
    }

    /**
     * Devuelve la url para editar un contenido.
     *
     * @return string Devuelve una cadena con la ruta para editar este
     *                contenido.
     */
    public function getUrlEditAttribute()
    {
        return route('dashboard.content.edit', ['model' => $this->id]);
    }

    /**
     * Añade una nueva página al contenido.
     */
    public function addPage(): ContentPage
    {
        $lastPageOrder = $this->pages()->max('order');
        $this->touch();

        return ContentPage::create([
            'content_id' => $this->id,
            'title' => uniqid(),
            'slug' => uniqid(),
            'order' => ++$lastPageOrder,
        ]);
    }

    /**
     * Almacena los contribuidores del contenido, previamente borrará los
     * existentes si los hubiera.
     *
     * @param  array  $contributors  Es un array con los ids de los usuarios.
     * @return void
     */
    public function saveContributors(array $contributors)
    {
        $contributors = array_unique(array_filter($contributors));

        if (isset($contributors[auth()->id()])) {
            unset($contributors[auth()->id()]);
        }

        if (! $contributors || ! count($contributors)) {
            $this->contributors()->delete();

            return;
        }

        $contributorsToDelete = $this->contributors()->pluck('user_id')->diff($contributors);

        $contributorsToDelete->each(function ($contributor) {
            ContentContributor::where('user_id', $contributor)
                ->where('content_id', $this->id)
                ->delete();
        });

        $contributorsStored = $this->contributors()
            ->whereIn('user_id', $contributors)
            ->pluck('user_id')
            ->toArray();

        foreach ($contributors as $contributor) {
            if (in_array($contributor, $contributorsStored)) {
                continue;
            }

            $this->contributorsJoin()->create([
                'user_id' => $contributor,
                'content_id' => $this->id,
            ]);
        }
    }

    /**
     * Almacena las etiquetas asociadas al contenido.
     *
     * @param  array  $tags  Es un array con los ids de las etiquetas.
     */
    public function saveTags(array $tags): void
    {

        // # Limpio etiquetas vacías y duplicadas.
        $tags = array_unique(array_filter($tags));

        $platformTags = $this->platform->tags()
            ->whereIn('tag_id', $tags)
            ->pluck('tag_id')
            ->toArray();

        // # Almacena las etiquetas que aún no están asociadas a la plataforma.
        $platformTagsDiff = array_diff($tags, $platformTags);

        // # Creamos las etiquetas que no estén asociadas a la plataforma.
        foreach ($platformTagsDiff as $platformTag) {
            $this->platform->tags()->create([
                'tag_id' => $platformTag,
            ]);
        }

        // # Etiquetas ya asociadas al contenido
        $contentTags = $this->tagsJoin()
            ->pluck('platform_tag_id')
            ->toArray();

        // # Borramos las etiquetas que no estén en el array, ya no forma parte del contenido.
        foreach ($contentTags as $contentTag) {
            if (! in_array($contentTag, $tags)) {
                ContentTag::where('content_id', $this->id)
                    ->where('platform_tag_id', $contentTag)
                    ->delete();
            }
        }

        // # Vuelvo a buscar las etiquetas asociadas al contenido, ya que puede haber cambiado
        $platformTags = PlatformTag::select(['id', 'tag_id'])
            ->whereIn('tag_id', $tags)
            ->where('platform_id', $this->platform_id)
            ->get();

        // # Cada etiqueta que no esté asociada al contenido, la creamos.
        foreach ($tags as $tag) {
            if (in_array($tag, $contentTags)) {
                continue;
            }

            $platformTag = $platformTags->where('tag_id', $tag)->first();

            if (! $platformTag) {
                continue;
            }

            $this->tagsJoin()->updateOrCreate([
                'content_id' => $this->id,
                'platform_tag_id' => $platformTag->id,
            ]);
        }

    }

    /**
     * Almacena las categorías del contenido, previamente borrará los
     * existentes si los hubiera.
     *
     * @param  array  $categories  Es un array con los ids de las categorías.
     * @return void
     */
    public function saveCategories(array $categories, array $subcategories = [])
    {
        // # Limpio categorías vacías y duplicadas.
        $categories = array_unique(array_filter($categories));
        $subcategories = array_unique(array_filter($subcategories));

        $allCategories = array_merge($categories, $subcategories);

        // # Categorías ya asociadas al contenido
        $contentCategories = $this->categoriesJoin()
            ->pluck('platform_category_id')
            ->toArray();

        // dd($allCategories, $contentCategories);

        // # Borramos las categorías que no estén en el array, ya no forma parte del contenido.
        foreach ($contentCategories as $contentCategory) {
            if (! in_array($contentCategory, $allCategories)) {

                ContentCategory::where('content_id', $this->id)
                    ->where('platform_category_id', $contentCategory)
                    ->delete();
            }
        }

        // # Vuelvo a buscar las categorías asociadas al contenido, ya que puede haber cambiado
        $platformCategories = PlatformCategory::select(['id', 'category_id'])
            ->whereIn('category_id', $allCategories)
            ->where('platform_id', $this->platform_id)
            ->get();

        // # Cada categoría que no esté asociada al contenido, la creamos en la tabla de join.
        foreach ($allCategories as $category) {
            if (in_array($category, $contentCategories)) {
                continue;
            }

            $platformCategory = $platformCategories->where('category_id', $category)->first();

            if (! $platformCategory) {
                continue;
            }

            $this->categoriesJoin()->updateOrCreate([
                'content_id' => $this->id,
                'platform_category_id' => $platformCategory->id,
            ]);
        }

    }

    /**
     * Elimina el contenido de la plataforma y lo que tenga asociado.
     */
    public function safeDelete(): bool
    {
        $this->seo?->safeDelete();

        $pages = $this->pages;

        if ($pages->count()) {
            foreach ($pages as $page) {
                $page->safeDelete();
            }
        }

        return parent::safeDelete();
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     */
    protected static function getPolicy(): ?string
    {
        return ContentPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
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
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'image_id' => 'Imagen ID',
            'urlImage' => 'Imagen',
            'title' => 'Título',
            'published_at' => 'Publicado',
            'slug' => 'Slug',
            'excerpt' => 'Resumen',
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

    /**
     * Scope para filtrar por plataforma.
     */
    public function scopeForPlatform(Builder $query, int $platformId): Builder
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * Scope para filtrar contenidos publicados.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status_id', 2);
    }

    /**
     * Scope para filtrar contenidos destacados.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope para filtrar por tipo de contenido.
     */
    public function scopeOfType(Builder $query, int $typeId): Builder
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Scope para filtrar contenidos programados.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status_id', 3);
    }
}
