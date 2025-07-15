<?php

namespace App\Models;

use App;
use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use function route;
use App\Models\Content\Content;
use App\Helpers\ContentHelper;

/**
 * Class Platform
 */
class Platform extends BaseAbstractModelWithTableCrud
{
    use ImageTrait;

    protected $table = 'platforms';

    //protected $with = ['image'];
    protected $appends = ['urlImageMicro', 'urlImageSmall'];

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'domain', 'url_about', 'youtube_channel_id',
        'youtube_presentation_video_id', 'twitter', 'twitter_token', 'mastodon', 'mastodon_token', 'twitch', 'tiktok',
        'instagram'
        ];


    public static function  getModuleName(): string
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
            //$model->cleanAllCache(); // Es mejor hacerlo en store/update para tener la asociación de categorías
            //\Log::info('El modelo Platform ha disparado saved:', ['modelo' => $model]);
        });

        // Evento "updated": Solo se dispara cuando el modelo es actualizado
        static::updated(function ($model) {
            //$model->cleanAllCache();
            //\Log::info('El modelo Platform ha disparado updated:', ['modelo' => $model]);
        });
    }

    /**
     * Asocia con el usuario al que pertenece la plataforma.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     *
     * @return HasMany
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'platform_id', 'id')
            ->orderByDesc('contents.is_featured')
            ->orderByDesc('contents.updated_at');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     *
     * @return HasMany
     */
    public function contentsActive(): HasMany
    {
        return $this->contents()
            ->where('contents.is_active', true)
            ->whereNotNull('contents.published_at')
            ;
    }

    /**
     * Devuelve el contenido de tipo páginas asociado a la plataforma actual.
     *
     * @return HasMany
     */
    public function contentPages(): HasMany
    {
        return $this->contentsActive()->where('type_id', 1);
    }

    /**
     * Asocia todos los tags para la plataforma.
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'platform_tags', 'platform_id', 'tag_id');
    }

    /**
     * Asocia todas las categorías para la plataforma.
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'platform_categories', 'platform_id', 'category_id');
    }

    /**
     * Asocia a la imagen principal.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Devuelve todos los dominios asignados a las plataformas.
     *
     * @return array
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
     *
     * @return void
     */
    public function cleanApiCategoryCache(): void
    {
        Cache::forget('api-categories-' . $this->slug);
        $this->getApiCategories();
    }

    /**
     * Limpia y renueva el caché para los contenidos destacados asociados a la plataforma.
     *
     * @return void
     */
    public function cleanContentFeaturedCache(): void
    {
        Cache::forget('api-content-featured-' . $this->slug);
        $this->getContentFeatured();
    }

    /**
     * Limpia y renueva el caché para los últimos contenidos asociados a la plataforma.
     *
     * @return void
     */
    public function cleanContentLatestCache(): void
    {
        Cache::forget('api-content-latest-' . $this->slug);
        $this->getContentLatest();
    }

    /**
     * Limpia y renueva el caché para los últimos contenidos en tendencia por visitas.
     *
     * @return void
     */
    public function cleanContentTrendCache(): void
    {
        Cache::forget('api-content-trend-' . $this->slug);
        $this->getContentTrend();
    }

    /**
     * Limpia y renueva aquello que se haya cacheado para la plataforma, útil para recomponer datos después
     * de crear o actualizar una.
     *
     * @return void
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
        $fields = ['contents.id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at'];

        // Fecha de hace 3 días
        $threeDaysAgo = now()->subDays(3)->format('Y-m-d');

        return $this->contentsActive()
            ->select($fields)
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
     *
     * @return array
     */
    public function getContentTrend(): array
    {
        return Cache::remember('api-content-trend-' . $this->slug, 60*60, function () {
            $posts = $this->getContentTrendByType('blog');
            $news = $this->getContentTrendByType('news');
            $guides = $this->getContentTrendByType('guide');

            return [
                'blog' => ContentHelper::contentFeaturedPrepareAll($posts),
                'news' => ContentHelper::contentFeaturedPrepareAll($news),
                'guides' => ContentHelper::contentFeaturedPrepareAll($guides),
            ];
        });
    }

    public function getContentFeaturedByType(string $type, int $limit = 6): Collection
    {
        $fields = ['contents.id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at'];

        return $this->contentsActive()
            ->select($fields)
            ->whereHas('type', function ($query) use ($type) {
                $query->where('slug', $type);
            })
            ->whereIn('contents.is_featured', [true])
            //->orderByDesc('contents.is_featured')
            ->orderByDesc('contents.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Devuelve el contenido destacado formateado para consumirla a través de api.
     *
     * @return array
     */
    public function getContentFeatured(): array
    {
        return Cache::rememberForever('api-content-featured-' . $this->slug, function () {
            $posts = $this->getContentFeaturedByType('blog');
            $news = $this->getContentFeaturedByType('news');
            $guides = $this->getContentFeaturedByType('guide');

            return [
                'blog' => ContentHelper::contentFeaturedPrepareAll($posts),
                'news' => ContentHelper::contentFeaturedPrepareAll($news),
                'guides' => ContentHelper::contentFeaturedPrepareAll($guides),
            ];
        });
    }

    /**
     * Devuelve el último contenido
     *
     * @param string $type
     * @param int $limit
     * @return Collection
     */
    public function getContentLatestByType(string $type, int $limit = 6): Collection
    {
        $fields = ['contents.id', 'contents.image_id', 'contents.platform_id', 'contents.title', 'contents.slug', 'contents.excerpt', 'contents.published_at', 'contents.updated_at'];

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
     *
     * @return array
     */
    public function getContentLatest(): array
    {
        return Cache::rememberForever('api-content-latest-' . $this->slug, function () {
            $posts = $this->getContentLatestByType('blog');
            $news = $this->getContentLatestByType('news');
            $guides = $this->getContentLatestByType('guide');

            return [
                'blog' => ContentHelper::contentFeaturedPrepareAll($posts),
                'news' => ContentHelper::contentFeaturedPrepareAll($news),
                'guides' => ContentHelper::contentFeaturedPrepareAll($guides),
            ];
        });
    }

    /**
     * Devuelve todas las categorías formateadas para consumirla a través de api.
     * Estas categorías se cachean automáticamente al editarlas.
     *
     * @return Collection
     */
    public function getApiCategories(): Collection
    {
        return Cache::rememberForever('api-categories-' . $this->slug, function () {
            $categories = $this->categories()
                ->select('categories.id', 'categories.parent_id', 'categories.slug', 'categories.name', 'categories.description', 'categories.icon', 'categories.color', 'categories.image_id')
                ->where('parent_id', null)
                ->with('subcategories', function ($query) {
                    $query->select('id', 'parent_id', 'slug', 'name', 'description', 'icon', 'color', 'image_id');
                })
                ->with('image')
                ->orderBy('categories.name')
                ->get()
            ;

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
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return App\Policies\PlatformPolicy::class;
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
