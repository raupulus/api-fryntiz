<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlatformColorRoleEnum;
use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\Concerns\BumpsApiCache;
use App\Models\Content\Content;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
 * @property string|null $system_instruction Instrucción editorial por defecto del asistente IA
 * @property array<int, array{hex?: string, role?: string|null}>|null $theme_colors Colores del tema de su web, para generar imágenes con IA
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, PlatformCategory> $platformCategories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Content> $contentPages
 * @property-read int|null $content_pages_count
 * @property-read Collection<int, Content> $contents
 * @property-read int|null $contents_count
 * @property-read Collection<int, Content> $contentsActive
 * @property-read int|null $contents_active_count
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 * @property-read Collection<int, Tag> $tags
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
        'instagram', 'system_instruction', 'theme_colors',
    ];

    /**
     * Como mucho, los colores del tema que se guardan.
     */
    public const MAX_THEME_COLORS = 5;

    protected $casts = [
        'theme_colors' => 'array',
    ];

    /**
     * Los colores del tema que valen: un hex de 6 cifras y, si se ha dicho,
     * para qué lo usa la web. Lo que no valga se ignora.
     *
     * @return list<array{hex: string, role: PlatformColorRoleEnum|null}>
     */
    public function themeColors(): array
    {
        $colors = [];

        foreach (is_array($this->theme_colors) ? $this->theme_colors : [] as $color) {
            $hex = is_array($color) ? strtoupper(trim((string) ($color['hex'] ?? ''))) : '';

            if (preg_match('/^#[0-9A-F]{6}$/', $hex) !== 1) {
                continue;
            }

            $colors[] = ['hex' => $hex, 'role' => PlatformColorRoleEnum::tryFrom((string) ($color['role'] ?? ''))];
        }

        return array_slice($colors, 0, self::MAX_THEME_COLORS);
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
     * Las filas de la pivote con sus categorías (`platform_categories`): se
     * gestionan desde la plataforma y es a lo que se enlazan los contenidos.
     */
    public function platformCategories(): HasMany
    {
        return $this->hasMany(PlatformCategory::class, 'platform_id', 'id');
    }

    /**
     * Los perfiles de editor del asistente IA en esta plataforma.
     *
     * @return HasMany<PlatformEditorProfile, $this>
     */
    public function editorProfiles(): HasMany
    {
        return $this->hasMany(PlatformEditorProfile::class, 'platform_id')->orderBy('name');
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
}
