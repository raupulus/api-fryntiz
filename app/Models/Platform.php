<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\Concerns\BumpsApiCache;
use App\Models\Content\Content;
use App\Support\ApiCacheVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
class Platform extends BaseModel
{
    use BumpsApiCache;
    use HasFactory, ImageTrait;
    use SoftDeletes;

    protected $table = 'platforms';

    // protected $with = ['image'];
    protected $appends = ['urlImageMicro', 'urlImageSmall'];

    protected $fillable = ['user_id', 'image_id', 'title', 'slug', 'description', 'domain', 'url_about', 'youtube_channel_id',
        'youtube_presentation_video_id', 'twitter', 'twitter_token', 'mastodon', 'mastodon_token', 'twitch', 'tiktok',
        'instagram',
    ];

    /**
     * Asocia con el usuario al que pertenece la plataforma.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     *
     * @return HasMany<Content, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'platform_id', 'id')
            ->orderByDesc('contents.is_featured')
            ->orderByDesc('contents.updated_at');
    }

    /**
     * Contenidos que se sirven a las webs: publicados y activos
     * (`Content::scopePublished()`, la única definición de «publicado»).
     *
     * @return HasMany<Content, $this>
     */
    public function contentsActive(): HasMany
    {
        return $this->contents()->published();
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
     * Devuelve todas las categorías formateadas para consumirla a través de api.
     *
     * En caché con la versión de `ApiCacheVersion`: antes era para siempre y
     * sólo se renovaba al guardar una categoría, no al añadirla a la
     * plataforma (F9).
     */
    public function getApiCategories(): Collection
    {
        return Cache::remember('api-categories-'.$this->id.'-'.ApiCacheVersion::current(), now()->addDay(), function () {
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
}
