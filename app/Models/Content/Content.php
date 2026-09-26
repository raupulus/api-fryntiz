<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\ContentStatusEnum;
use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\Category;
use App\Models\ContentDailyView;
use App\Models\File;
use App\Models\Gallery;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use App\Services\Content\ContentFileUsageService;
use App\Traits\HasGalleries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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
 * @property-read Collection<int, ContentCategory> $categoriesJoin
 * @property-read int|null $categories_join_count
 * @property-read Collection<int, Content> $contentsRelated
 * @property-read int|null $contents_related_count
 * @property-read Collection<int, Content> $contentsRelatedAllPlatforms
 * @property-read int|null $contents_related_all_platforms_count
 * @property-read Collection<int, Content> $contentsRelatedMe
 * @property-read int|null $contents_related_me_count
 * @property-read Collection<int, Content> $contentsRelatedMeAllPlatforms
 * @property-read int|null $contents_related_me_all_platforms_count
 * @property-read Collection<int, User> $contributors
 * @property-read int|null $contributors_count
 * @property-read Collection<int, ContentContributor> $contributorsJoin
 * @property-read int|null $contributors_join_count
 * @property-read Collection<int, ContentDailyView> $dailyViews
 * @property-read int|null $daily_views_count
 * @property-read Collection<int, Gallery> $galleries
 * @property-read int|null $galleries_count
 * @property-read mixed $categories
 * @property-read mixed $subcategories
 * @property-read mixed $tags
 * @property-read mixed $url
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read mixed $url_preview
 * @property-read File|null $image
 * @property-read ContentMetadata|null $metadata
 * @property-read Collection<int, ContentPage> $pages
 * @property-read int|null $pages_count
 * @property-read Platform|null $platform
 * @property-read ContentSeo|null $seo
 * @property-read ContentAvailableStatus|null $status
 * @property-read Collection<int, ContentTag> $tagsJoin
 * @property-read int|null $tags_join_count
 * @property-read Collection<int, PlatformTag> $tagsPlatform
 * @property-read int|null $tags_platform_count
 * @property-read Collection<int, Technology> $technologies
 * @property-read int|null $technologies_count
 * @property-read Collection<int, ContentTechnology> $technologiesJoin
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
class Content extends BaseModel
{
    use HasFactory, HasGalleries, ImageTrait;
    use SoftDeletes;

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

    protected static function boot()
    {
        parent::boot();

        // Reglas de publicación. Van aquí, y no en el formulario, para que se
        // cumplan igual desde el panel, la acción masiva, el cron o cualquier
        // otro sitio que guarde un contenido.
        static::saving(fn (Content $model) => $model->applyPublicationRules());

        // Colaboradores automáticos de la plataforma (DUDA-1).
        static::created(fn (Content $model) => app(ContentContributorService::class)->applyToNewContent($model));

        // Al eliminarlo definitivamente, sus ficheros quedan sin usar (C2): la
        // tarea diaria los borra a los 30 días. Irse a la papelera no, porque
        // se puede restaurar.
        static::forceDeleting(fn (Content $model) => app(ContentFileUsageService::class)->markAllOf($model->id));

        // Evento "saved": Se dispara después de ser guardado por primera vez y tras actualizarse
        static::saved(function (Content $model) {
            // La plataforma se carga aparte, sola. La que cuelga del contenido
            // puede venir de una carga de varios (la tabla del panel, el cron
            // de publicar) y, con la carga perezosa bloqueada fuera de
            // producción, regenerar su caché reventaba al publicar dos a la vez.
            $model->platform()->first()?->cleanAllCache();
        });

        // Evento "updated": Solo se dispara cuando el modelo es actualizado
        static::updated(function ($model) {
            // $model->cleanAllCache();
            // \Log::info('El modelo Platform ha disparado updated:', ['modelo' => $model]);
        });
    }

    /**
     * Estado como enum; null en los contenidos sin estado (los que vienen de la
     * v1 lo tienen vacío).
     */
    public function statusEnum(): ?ContentStatusEnum
    {
        return $this->status_id === null ? null : ContentStatusEnum::tryFrom((int) $this->status_id);
    }

    /**
     * ¿Está en estado «publicado»? Que salga en las webs depende además de
     * «Activo»: ver `scopePublished()`.
     */
    public function isPublished(): bool
    {
        return $this->statusEnum() === ContentStatusEnum::Published;
    }

    /**
     * Publica el contenido y lo deja visible.
     *
     * La fecha de publicación la pone `applyPublicationRules()` al guardar. Si
     * ya estaba publicado pero oculto, vuelve a verse: «Publicar» siempre deja
     * el contenido en las webs.
     */
    public function publish(): void
    {
        $this->status_id = ContentStatusEnum::Published->value;
        $this->is_active = true;
        $this->save();
    }

    /**
     * Reglas de publicación (P1 y DUDA-3 de la auditoría de contenidos del
     * 2026-09-24), aplicadas en cada guardado:
     *
     * - Al pasar a «publicado», desde donde sea: fecha de publicación de ese
     *   momento si no tenía, y «Activo» marcado.
     * - Publicado es definitivo: no vuelve a ningún otro estado. Se retira de
     *   las webs desmarcando «Activo», o se elimina.
     * - «Borrador» no tiene fecha de publicación.
     * - «Programado» necesita la fecha en la que publicarse (que sea futura lo
     *   valida el formulario: el cron publica lo que ya ha pasado).
     * - Los demás estados no tocan las fechas.
     *
     * @throws ValidationException si se intenta sacar de «publicado» o programar sin fecha.
     */
    public function applyPublicationRules(): void
    {
        $status = $this->statusEnum();
        $original = $this->exists ? ContentStatusEnum::tryFrom((int) $this->getOriginal('status_id')) : null;

        if ($original === ContentStatusEnum::Published && $status !== ContentStatusEnum::Published) {
            throw ValidationException::withMessages([
                'status_id' => 'Un contenido publicado no cambia de estado. Para retirarlo de las webs, desmarca «Activo»; para quitarlo del todo, elimínalo.',
            ]);
        }

        if ($status === ContentStatusEnum::Published) {
            if ($original !== ContentStatusEnum::Published) {
                $this->is_active = true;
            }

            $this->published_at ??= now();

            return;
        }

        if ($status === ContentStatusEnum::Draft) {
            $this->published_at = null;

            return;
        }

        if ($status === ContentStatusEnum::Scheduled && $this->scheduled_at === null) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Para programar un contenido hace falta la fecha en la que se publica.',
            ]);
        }
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
     *
     * @return HasMany<ContentPage, $this>
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
            ->wherePivotNull('deleted_at')
            ->where('contents.platform_id', $this->platform_id);
    }

    /**
     * Relación con el contenido actual asociado a otros de cualquier
     * plataforma.
     */
    public function contentsRelatedAllPlatforms(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_id', 'content_related_id')
            ->wherePivotNull('deleted_at');
    }

    /**
     * Relación con el contenido asociado al actual en la plataforma actual.
     */
    public function contentsRelatedMe(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_related_id', 'content_id')
            ->wherePivotNull('deleted_at')
            ->where('contents.platform_id', $this->platform_id);
    }

    /**
     * Relación con el contenido asociado al actual para cualquier plataforma.
     */
    public function contentsRelatedMeAllPlatforms(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_related_id', 'content_id')
            ->wherePivotNull('deleted_at');
    }

    /**
     * Relación con los colaboradores asociados al contenido.
     */
    /**
     * @return BelongsToMany<User, $this>
     */
    public function contributors(): BelongsToMany
    {
        // Las tablas intermedias de contenidos tienen borrado lógico, y una
        // fila borrada es un colaborador quitado: sin este filtro seguiría
        // contando como colaborador y podría seguir editando.
        return $this->belongsToMany(User::class, 'content_contributors', 'content_id', 'user_id')
            ->wherePivotNull('deleted_at');
    }

    /**
     * ¿Es colaborador (y no se le ha quitado)?
     */
    public function hasContributor(User $user): bool
    {
        return $this->contributors()->whereKey($user->id)->exists();
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
        return $this->belongsToMany(Technology::class, 'content_technologies', 'content_id', 'technology_id')
            ->wherePivotNull('deleted_at');
    }

    /**
     * Relación con la tabla intermedia de las tecnologías.
     */
    public function technologiesJoin(): HasMany
    {
        return $this->hasMany(ContentTechnology::class, 'content_id', 'id');
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
            ->where('content_categories.content_id', $this->id)
            ->whereNull('content_categories.deleted_at')
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
     * @return Builder<Category>
     */
    public function subcategoriesQuery(?int $platformId = null): Builder
    {
        $categoriesId = Category::select('categories.id')
            ->leftJoin('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->leftJoin('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id')
            ->where('content_categories.content_id', $this->id)
            ->whereNull('content_categories.deleted_at')
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
            ->where('content_tags.content_id', $this->id)
            ->whereNull('content_tags.deleted_at')
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
        return $this->belongsToMany(PlatformTag::class, 'content_tags', 'content_id', 'platform_tag_id')
            ->wherePivotNull('deleted_at');
    }

    /**
     * Relación con la plataforma asociada al contenido
     *
     * @return BelongsTo<Platform, $this>
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
     * Deja como colaboradores exactamente estos usuarios.
     *
     * Los que sobran se quitan (su fila queda borrada: es una baja manual, que
     * el colaborador automático respeta) y los que vuelven se recuperan. Sólo
     * se toca la relación, nunca a los usuarios: antes, con una lista vacía,
     * `contributors()->delete()` borraba los usuarios colaboradores. El autor no
     * es colaborador de lo suyo.
     *
     * @param  array<int|string|null>  $contributors  Ids de los usuarios.
     */
    public function saveContributors(array $contributors): void
    {
        $service = app(ContentContributorService::class);
        $wanted = array_values(array_unique(array_map('intval', array_filter($contributors))));

        foreach ($this->contributors()->get() as $current) {
            if (! in_array((int) $current->id, $wanted, true)) {
                $service->remove($this, $current);
            }
        }

        foreach (User::query()->whereIn('id', $wanted)->get() as $user) {
            $service->add($this, $user);
        }
    }

    /**
     * Deja como etiquetas del contenido exactamente estas.
     *
     * Recibe ids de `tags`; lo que se guarda en `content_tags` es la etiqueta
     * **de la plataforma** (`platform_tags`), que se crea si la plataforma aún
     * no la tenía. Las que sobran se quitan (fila borrada) y las que vuelven se
     * recuperan. Antes se comparaban ids de `tags` con ids de `platform_tags` y
     * se quitaban etiquetas que seguían marcadas.
     *
     * @param  array<int|string|null>  $tags  Ids de `tags`.
     */
    public function saveTags(array $tags): void
    {
        $tagIds = array_values(array_unique(array_map('intval', array_filter($tags))));
        $platformTagIds = [];

        foreach ($tagIds as $tagId) {
            $platformTagIds[] = (int) PlatformTag::query()->firstOrCreate([
                'platform_id' => $this->platform_id,
                'tag_id' => $tagId,
            ])->id;
        }

        ContentTag::query()
            ->where('content_id', $this->id)
            ->whereNotIn('platform_tag_id', $platformTagIds)
            ->delete();

        foreach ($platformTagIds as $platformTagId) {
            $row = ContentTag::withTrashed()->firstOrNew(['content_id' => $this->id, 'platform_tag_id' => $platformTagId]);

            if ($row->trashed()) {
                $row->restore();
            } elseif (! $row->exists) {
                $row->save();
            }
        }
    }

    /**
     * Deja como categorías y subcategorías del contenido exactamente estas.
     *
     * Recibe ids de `categories`; lo que se guarda en `content_categories` es la
     * categoría **de la plataforma** (`platform_categories`): las que la
     * plataforma no tiene se ignoran. Las que sobran se quitan (fila borrada) y
     * las que vuelven se recuperan. Con `$mainCategoryId`, ésa queda como
     * principal (`is_main`) y las demás no.
     *
     * @param  array<int|string|null>  $categories  Ids de `categories`.
     * @param  array<int|string|null>  $subcategories  Ids de `categories`.
     */
    public function saveCategories(array $categories, array $subcategories = [], ?int $mainCategoryId = null): void
    {
        $categoryIds = array_values(array_unique(array_map('intval', array_filter(array_merge($categories, $subcategories)))));

        $platformCategories = PlatformCategory::query()
            ->where('platform_id', $this->platform_id)
            ->whereIn('category_id', $categoryIds)
            ->pluck('category_id', 'id');

        ContentCategory::query()
            ->where('content_id', $this->id)
            ->whereNotIn('platform_category_id', $platformCategories->keys()->all())
            ->delete();

        foreach ($platformCategories as $platformCategoryId => $categoryId) {
            $row = ContentCategory::withTrashed()->firstOrNew(['content_id' => $this->id, 'platform_category_id' => $platformCategoryId]);

            if ($row->trashed()) {
                $row->restore();
            }

            if ($mainCategoryId !== null) {
                $row->is_main = (int) $categoryId === $mainCategoryId;
            }

            $row->save();
        }
    }

    /**
     * A la papelera, sin tocar sus ficheros ni su SEO: se puede restaurar.
     *
     * Antes borraba en el acto la imagen SEO, la portada y los ficheros de sus
     * páginas mientras el contenido se quedaba en la papelera (borrado
     * lógico): al restaurarlo, las imágenes ya no estaban. Los ficheros se
     * limpian al eliminarlo definitivamente (C2, `ContentFileUsageService`).
     */
    public function safeDelete(): bool
    {
        return (bool) $this->delete();
    }

    /**
     * Scope para filtrar por plataforma.
     */
    public function scopeForPlatform(Builder $query, int $platformId): Builder
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * Lo que se sirve a las webs: estado «publicado» **y** «Activo».
     *
     * Es la única definición de «publicado» del código: la usan la API, las
     * fichas de plataforma (`Platform::contentsActive()`) y las estadísticas
     * por tipo (`ContentAvailableType::contentsActive()`).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status_id'), ContentStatusEnum::Published->value)
            ->where($this->qualifyColumn('is_active'), true);
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
        return $query->where($this->qualifyColumn('status_id'), ContentStatusEnum::Scheduled->value);
    }
}
