<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use App\Models\User;
use App\Services\Content\ContentFileUsageService;
use App\Traits\HasGalleries;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class ContentPage
 *
 * @property int $id
 * @property int|null $content_id FK al usuario propietario del post
 * @property int|null $image_id FK a la imagen en la tabla files
 * @property int|null $current_page_raw_id FK al tipo de contenido desde el que se ha procesado el html actual para el campo "content". En caso de ser NULL, se está utilizando directamente el campo "content"
 * @property string|null $title Título de la página
 * @property string|null $slug Slug de la página
 * @property string|null $content Contenido de la página en html procesado
 * @property int|null $order Orden de la página al mostrarse
 * @property int|null $locked_by_user_id Quién la tiene bloqueada (ver ContentPageLockService)
 * @property Carbon|null $locked_since
 * @property Carbon|null $locked_at
 * @property string|null $lock_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Content|null $contentModel
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 * @property-read Collection<int, ContentPageRaw> $raws
 * @property-read ContentAvailablePageRaw|null $currentRawType
 * @property-read Collection<int, ContentPageVersion> $versions
 * @property-read Collection<int, ContentPageDraft> $drafts
 * @property-read User|null $lockedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereCurrentPageRawId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentPage extends BaseModel
{
    use HasGalleries;
    use ImageTrait;
    use SoftDeletes;

    protected $table = 'content_pages';

    protected $fillable = [
        'current_page_raw_id',
        'content_id',
        'image_id',
        'title',
        'slug',
        'content',
        'order',
    ];

    protected $casts = [
        'locked_since' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Eliminada definitivamente, sus ficheros pueden quedarse sin usar
        // (C2). A la papelera no: se puede restaurar.
        static::forceDeleted(fn (ContentPage $page) => app(ContentFileUsageService::class)->refresh($page->content_id));
    }

    /**
     * Versiones de la página en cada formato (Editor.js, Markdown, HTML).
     *
     * Una es la fuente, la que marca `current_page_raw_id`; las demás se
     * regeneran a partir de ella al guardar. Quien sabe leerlas es
     * `ContentPageFormatService`. (Las versiones anteriores están en
     * `versions()`.)
     *
     * @return HasMany<ContentPageRaw, $this>
     */
    public function raws(): HasMany
    {
        return $this->hasMany(ContentPageRaw::class, 'content_page_id', 'id');
    }

    /**
     * Historial: lo que había antes de cada cambio (`ContentPageHistoryService`).
     *
     * @return HasMany<ContentPageVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContentPageVersion::class, 'content_page_id', 'id');
    }

    /**
     * Borradores de esta página, uno por usuario (`ContentPageDraftService`).
     *
     * @return HasMany<ContentPageDraft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(ContentPageDraft::class, 'content_page_id', 'id');
    }

    /**
     * Quién la tiene bloqueada. Para saber si el bloqueo sigue vigente, ver
     * `ContentPageLockService::state()`.
     *
     * @return BelongsTo<User, $this>
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id', 'id');
    }

    /**
     * Tipo de la fuente de la página (el formato en el que se edita).
     *
     * @return BelongsTo<ContentAvailablePageRaw, $this>
     */
    public function currentRawType(): BelongsTo
    {
        return $this->belongsTo(ContentAvailablePageRaw::class, 'current_page_raw_id', 'id');
    }

    /**
     * Relación con la imagen principal de la página.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Relación con el contenido al que pertenece la página.
     */
    public function contentModel(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Obtiene el contenido según el tipo especificado
     *
     * @return mixed
     */
    public function getContentByType(string $type = 'html'): string
    {
        if (! $type || $type === 'html') {
            return $this->content;
        }

        $contentRaw = ContentPageRaw::where('content_page_raw.content_page_id', $this->id)
            ->leftJoin('content_available_page_raw', 'content_available_page_raw.id', '=', 'content_page_raw.available_page_raw_id')
            ->where('content_available_page_raw.type', $type)
            ->first();

        return $contentRaw?->content ?? $this->content;
    }

    /**
     * A la papelera, y las páginas que van detrás suben un puesto.
     *
     * Sus ficheros se quedan: se puede restaurar. Antes los borraba en el acto
     * del disco (también la portada) mientras la página se quedaba en la
     * papelera. Se limpian al eliminarla definitivamente (C2,
     * `ContentFileUsageService`).
     */
    public function safeDelete(): bool
    {
        $this->contentModel?->pages()
            ->where('order', '>', $this->order)
            ->get()
            ->each(function (ContentPage $page): void {
                $page->order--;
                $page->save();
            });

        return (bool) $this->delete();
    }

    /**
     * Limpia una cadena de texto, elimina html, entidades y espacios innecesarios.
     * También capitaliza la primera letra del texto.
     *
     * @param  string  $text  Cadena de texto a limpiar.
     */
    public static function sanitizeTitle(string $text): string
    {
        $title = trim(str_replace(['&amp;', '&nbsp;', '&#160;', '<p>', '<br>'], '', $text));
        $title = trim(strip_tags(html_entity_decode($title)));
        $title = trim(str_replace(['&amp;', '&nbsp;', '&#160;', '<p>', '<br>'], '', $title));
        $title = trim(strip_tags(html_entity_decode(preg_replace('/&#?[a-z0-9]+;/i', '', $title))));
        $title = trim(preg_replace("/\s+/", ' ', $title));

        return ucfirst($title);
    }
}
