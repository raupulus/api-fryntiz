<?php

declare(strict_types=1);

namespace App\Models;

use App;
use App\Http\Resources\ContentFeaturedResource;
use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Models\Content\Content;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function route;

/**
 * Class Platform
 *
 * @property int $id
 * @property int $user_id Relación con el usuario
 * @property int|null $image_id Relación con la imagen asociada
 * @property string $title Título de la sección
 * @property string $slug Slug para el URL
 * @property string|null $description Descripción breve de la sección
 * @property string|null $domain Dominio principal hacia la plataforma
 * @property string|null $url_about Página con información del proyecto
 * @property string|null $youtube_channel_id Identificador del canal en youtube
 * @property string|null $youtube_presentation_video_id Vídeo principal con la presentación del proyecto en youtube
 * @property string|null $twitter Usuario en twitter
 * @property string|null $twitter_token Token para la api de twitter
 * @property string|null $mastodon Usuario en mastodon
 * @property string|null $mastodon_token Token para la api de mastodon
 * @property string|null $twitch Usuario en twitch
 * @property string|null $tiktok Usuario en tiktok
 * @property string|null $instagram Usuario en instagram
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contentPages
 * @property-read int|null $content_pages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contents
 * @property-read int|null $contents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Content> $contentsActive
 * @property-read int|null $contents_active_count
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 * @property-read int|null $tags_count
 * @property-read User|null $user
 *
 * @method static \Database\Factories\PlatformFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereInstagram($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereMastodon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereMastodonToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereTiktok($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereTwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereTwitter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereTwitterToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereUrlAbout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereYoutubeChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Platform whereYoutubePresentationVideoId($value)
 *
 * @mixin \Eloquent
 */
class Platform extends BaseAbstractModelWithTableCrud
{
    use HasFactory, ImageTrait;

    protected $table = 'platforms';

    // protected $with = ['image'];
    protected $appends = ['urlImageMicro', 'urlImageSmall'];

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'domain', 'url_about', 'youtube_channel_id',
        'youtube_presentation_video_id', 'twitter', 'twitter_token', 'mastodon', 'mastodon_token', 'twitch', 'tiktok',
        'instagram',
    ];

    public static function getModuleName(): string
    {
        return 'platform';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Plataforma',
            'plural' => 'Plataformas',
            'add' => 'Agregar plataforma',
            'edit' => 'Editar plataforma',
            'delete' => 'Eliminar plataforma',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // Evento "saved": Se dispara después de ser guardado por primera vez y tras actualizarse
        static::saved(function ($model) {
            // $model->cleanAllCache(); // Es mejor hacerlo en store/update para tener la asociación de categorías
            // \Log::info('El modelo Platform ha disparado saved:', ['modelo' => $model]);
        });

        // Evento "updated": Solo se dispara cuando el modelo es actualizado
        static::updated(function ($model) {
            // $model->cleanAllCache();
            // \Log::info('El modelo Platform ha disparado updated:', ['modelo' => $model]);
        });
    }

    /**
     * Asocia con el usuario al que pertenece la plataforma.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'platform_id', 'id')
            ->orderByDesc('contents.is_featured')
            ->orderByDesc('contents.updated_at');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     */
    public function contentsActive(): HasMany
    {
        return $this->contents()
            ->where('contents.is_active', true)
            ->whereNotNull('contents.published_at');
    }

    /**
     * Devuelve el contenido de tipo páginas asociado a la plataforma actual.
     */
    public function contentPages(): HasMany
    {
        return $this->contentsActive()->where('type_id', 1);
    }

    /**
     * Asocia todos los tags para la plataforma.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'platform_tags', 'platform_id', 'tag_id');
    }

    /**
     * Asocia todas las categorías para la plataforma.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'platform_categories', 'platform_id', 'category_id');
    }

    /**
     * Asocia a la imagen principal.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Devuelve todos los dominios asignados a las plataformas.
     */
    public static function getAllDomains(): array
    {
        return self::whereNotNull('domain')
            ->whereNotIn('domain', ['', ' ', false])
            ->pluck('domain')
            ->toArray();
    }

    /**
     * Limpia y renueva el caché para las categorías asociadas a la plataforma.
     */
    public function cleanApiCategoryCache(): void
    {
        Cache::forget('api-categories-'.$this->slug);
        $this->getApiCategories();
    }

    /**
     * Limpia y renueva el caché para los contenidos destacados asociados a la plataforma.
     */
    public function cleanContentFeaturedCache(): void
    {
        Cache::forget('api-content-featured-'.$this->slug);
        $this->getContentFeatured();
    }

    /**
     * Limpia y renueva el caché para los últimos contenidos asociados a la plataforma.
     */
    public function cleanContentLatestCache(): void
    {
        Cache::forget('api-content-latest-'.$this->slug);
        $this->getContentLatest();
    }

    /**
     * Limpia y renueva el caché para los últimos contenidos en tendencia por visitas.
     */
    public function cleanContentTrendCache(): void
    {
        Cache::forget('api-content-trend-'.$this->slug);
        $this->getContentTrend();
    }

    /**
     * Limpia y renueva aquello que se haya cacheado para la plataforma, útil para recomponer datos después
     * de crear o actualizar una.
     */
    public function cleanAllCache(): void
    {
        $this->cleanApiCategoryCache();
        $this->cleanContentFeaturedCache();
        $this->cleanContentLatestCache();
        $this->cleanContentTrendCache();
    }

    public function getContentTrendByType(string $type, int $limit = 6): Collection
    {
        $fields = ['contents.id', 'contents.type_id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at'];

        // Fecha de hace 3 días
        $threeDaysAgo = now()->subDays(3)->format('Y-m-d');

        return $this->contentsActive()
            ->select($fields)
            // ->addSelect(DB::raw('content_available_types.name as type'))
            ->addSelect(DB::raw('COALESCE(SUM(content_daily_views.views), 0) as total_views'))
            ->leftJoin('content_daily_views', function ($join) use ($threeDaysAgo) {
                $join->on('contents.id', '=', 'content_daily_views.content_id')
                    ->where('content_daily_views.date', '>=', $threeDaysAgo);
            })
            ->whereHas('type', function ($query) use ($type) {
                $query->where('slug', $type);
            })
            ->groupBy('contents.id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at')
            ->orderByDesc('total_views')
            ->orderByDesc('contents.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Devuelve el contenido destacado formateado para consumirla a través de api.
     */
    public function getContentTrend(): array
    {
        return Cache::remember('api-content-trend-'.$this->slug, 60 * 60, function () {
            $posts = $this->getContentTrendByType('blog');
            $news = $this->getContentTrendByType('news');
            $guides = $this->getContentTrendByType('guide');

            return [
                'blog' => ContentFeaturedResource::collection($posts),
                'news' => ContentFeaturedResource::collection($news),
                'guides' => ContentFeaturedResource::collection($guides),
            ];
        });
    }

    public function getContentFeaturedByType(string $type, int $limit = 6): Collection
    {
        $fields = ['contents.id', 'contents.type_id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at'];

        return $this->contentsActive()
            ->select($fields)
            ->whereHas('type', function ($query) use ($type) {
                $query->where('slug', $type);
            })
            ->whereIn('contents.is_featured', [true])
            // ->orderByDesc('contents.is_featured')
            ->orderByDesc('contents.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Devuelve el contenido destacado formateado para consumirla a través de api.
     */
    public function getContentFeatured(): array
    {
        return Cache::rememberForever('api-content-featured-'.$this->slug, function () {
            $posts = $this->getContentFeaturedByType('blog');
            $news = $this->getContentFeaturedByType('news');
            $guides = $this->getContentFeaturedByType('guide');

            return [
                'blog' => ContentFeaturedResource::collection($posts),
                'news' => ContentFeaturedResource::collection($news),
                'guides' => ContentFeaturedResource::collection($guides),
            ];
        });
    }

    /**
     * Devuelve el último contenido
     */
    public function getContentLatestByType(string $type, int $limit = 6): Collection
    {
        $fields = ['contents.id', 'contents.type_id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at'];

        return $this->contentsActive()
            ->select($fields)
            ->whereHas('type', function ($query) use ($type) {
                $query->where('slug', $type);
            })
            ->whereNotIn('contents.is_featured', [true])
            ->orderByDesc('contents.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Devuelve el contenido destacado formateado para consumirla a través de api.
     */
    public function getContentLatest(): array
    {
        return Cache::rememberForever('api-content-latest-'.$this->slug, function () {
            $posts = $this->getContentLatestByType('blog');
            $news = $this->getContentLatestByType('news');
            $guides = $this->getContentLatestByType('guide');

            return [
                'blog' => ContentFeaturedResource::collection($posts),
                'news' => ContentFeaturedResource::collection($news),
                'guides' => ContentFeaturedResource::collection($guides),
            ];
        });
    }

    /**
     * Devuelve todas las categorías formateadas para consumirla a través de api.
     * Estas categorías se cachean automáticamente al editarlas.
     */
    public function getApiCategories(): Collection
    {
        return Cache::rememberForever('api-categories-'.$this->slug, function () {
            $categories = $this->categories()
                ->select('categories.id', 'categories.parent_id', 'categories.slug', 'categories.name', 'categories.description', 'categories.icon', 'categories.color', 'categories.image_id')
                ->where('parent_id', null)
                ->with('subcategories', function ($query) {
                    $query->select('id', 'parent_id', 'slug', 'name', 'description', 'icon', 'color', 'image_id');
                })
                ->with('image')
                ->orderBy('categories.name')
                ->get();

            // TODO: Revisar la forma de obtener subcategorías para optimizar esta parte y quitar esos unset.
            $categories->map(function ($category) {
                $category->urlImageMicro = $category->urlImageMicro;
                $category->urlImageSmall = $category->urlImageSmall;
                unset($category->id);
                unset($category->image_id);
                unset($category->image);
                unset($category->pivot);
                unset($category->parent_id);

                if ($category->subcategories) {
                    $category->subcategories->map(function ($subcategory) use ($category) {
                        $subcategory->urlImageMicro = $subcategory->urlImageMicro;
                        $subcategory->urlImageSmall = $subcategory->urlImageSmall;
                        unset($subcategory->id);
                        unset($subcategory->image_id);
                        unset($subcategory->image);
                        unset($subcategory->parent_id);

                        $subcategory->parent = $category->slug;

                        return $subcategory;
                    });
                }

                return $category;
            });

            return $categories;
        });
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     */
    protected static function getPolicy(): ?string
    {
        return App\Policies\PlatformPolicy::class;
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
            'image_id' => 'Imagen ID',
            'urlImage' => 'Imagen',
            'title' => 'Título',
            'slug' => 'Slug',
            'domain' => 'Dominio',
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
            'title' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'slug' => [
                'type' => 'text',
            ],
            'domain' => [
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
