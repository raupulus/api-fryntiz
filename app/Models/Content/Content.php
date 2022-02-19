<?php

namespace App\Models\Content;

use App\Models\File;
use App\Models\BaseModels\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use function route;
use function url;

/**
 * Class Content
 *
 * @package App\Models\Content
 */
class Content extends BaseModel
{
    use HasFactory;

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

    /**
     * Devuelve la relación con el autor/usuario que ha creado el contenido.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Devuelve la relación con el autor/usuario que ha creado el contenido.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->author();
    }

    /**
     * Devuelve la relación con el estado del contenido.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(ContentAvailableStatus::class, 'status_id', 'id');
    }

    /**
     * Devuelve la relación al tipo de contenido.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(ContentAvailableType::class, 'type_id', 'id');
    }

    /**
     * Relación con la tabla "files" que contiene la imagen principal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /**
     * Relación con los colaboradores asociados al contenido.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function collaborators()
    {
        return $this->hasMany(ContentContributor::class, 'content_id', 'id');
    }

    /**
     * Relación con las galerías asociadas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function galleries()
    {
        return $this->hasMany(ContentGallery::class, 'content_id', 'id');
    }

    /**
     * Relación al seo asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function seo()
    {
        return $this->hasOne(ContentSeo::class, 'content_id', 'id');
    }

    /**
     * Relación con las categorías asociadas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany(ContentCategory::class, 'content_id', 'id');
    }

    /**
     * Relación con las etiquetas asociadas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany(ContentTag::class, 'content_id', 'id');
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
     * editándose, en base al útimo guardado.
     * Útil para previsualizar borradores principalmente.
     */
    public function getUrlPreviewAttribute()
    {
        return url('TEMPORAL/URL/PAGINA/TMP/' . $this->slug);
    }

    /**
     * Devuelve la url para ver un contenido publicado.
     * Los administradores, propietario y colaboradores también pueden ver
     * borradores.
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
     * Elimina de forma segura este elemento y sus datos asociados.
     *
     * @return bool
     */
    public function safeDelete()
    {
        ## Elimino la imagen asociada y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }
}
