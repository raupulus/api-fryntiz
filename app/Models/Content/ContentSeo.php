<?php

namespace App\Models\Content;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Class ContentSeo
 *
 * @property int $id
 * @property int $content_id FK al contenido asociado
 * @property int|null $image_id FK a la imagen asociada
 * @property string|null $image_alt Título alternativo para la imagen de buscadores y redes sociales
 * @property string $distribution Distribución del contenido
 * @property string|null $keywords Palabras clave para SEO
 * @property string|null $revisit_after Sugiere a los motores de búsqueda que vuelvan a indexar la página después de un tiempo determinado
 * @property string|null $description Descripción
 * @property string|null $robots
 * @property string|null $og_title Título para Open Graph - Redes sociales
 * @property string $og_type Tipo de contenido para Open Graph - Redes sociales
 * @property string|null $twitter_card Tipo de tarjeta para Twitter - Redes sociales
 * @property string|null $twitter_creator Creador para Twitter - Redes sociales
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Content\Content $content
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereDistribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereImageAlt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereOgTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereOgType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereRevisitAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereRobots($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereTwitterCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereTwitterCreator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentSeo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContentSeo extends BaseModel
{
    use ImageTrait;

    protected $table = 'content_seo';

    protected $fillable = [
        'content_id',
        'image_id',
        'image_alt',
        'distribution',
        'keywords',
        'revisit_after',
        'description',
        'robots',
        'og_title',
        'og_type',
        'twitter_card',
        'twitter_creator',
    ];

    /**
     * Relación con el contenido al que pertenece.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Relación con el autor del contenido.
     */
    public function author(): BelongsTo
    {
        return $this->content->author();
    }

    /**
     * Relación con la imagen asociada al seo.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Devuelve todas las etiquetas genéricas.
     */
    public function getGenericTags(): Collection
    {
        return collect([
            'author' => $this->author->name,
            'copyright' => $this->author->name,
            'distribution' => $this->distribution, // global, local, iu
            'description' => $this->description,
            'robots' => $this->robots,
            'keywords' => $this->keywords,
            'revisit-after' => $this->revisit_after ?? '7 days',
            'published_time' => $this->content->created_at->toRfc3339String(), // 2023-07-22T05:59:00+01:00
            'modified_time' => $this->content->updated_at->toRfc3339String(),
            'article:published_time' => $this->content->created_at->toRfc3339String(),
            'article:modified_time' => $this->content->updated_at->toRfc3339String(),
        ]);
    }

    /**
     * Devuelve todas las etiquetas sociales.
     */
    public function getSocialTags(): Collection
    {
        $urlImageMedium = $this->urlThumbnail('normal');

        return collect([
            'og:title' => $this->og_title,
            'og:image' => $urlImageMedium,
            'og:image:alt' => $this->image_alt,
            'og:image:url' => $urlImageMedium,
            'og:image:secure_url' => $urlImageMedium,
            // 'og:image:type' => '', // TODO: Obtener desde relación con Files
            // 'og:image:width' => '', // TODO: Obtener desde relación con Files
            // 'og:image:height' => '', // TODO: Obtener desde relación con Files
            'og:type' => $this->og_type,
            'og:description' => $this->description,
        ]);
    }

    /**
     * Devuelve todas las etiquetas de Twitter.
     */
    public function getTwitterTags(): Collection
    {
        return collect([
            'twitter:card' => $this->twitter_card,
            'twitter:site' => '@raupulus',
            'twitter:title' => $this->twitter_title,
            'twitter:description' => $this->description,
            'twitter:creator' => $this->author->name,
            'twitter:image' => '', // TODO: Obtener desde relación con Files
            'twitter:image:alt' => $this->image_alt,
        ]);
    }

    /**
     * Devuelve todas las metaetiquetas del contenido.
     */
    public function getMetaTags(): Collection
    {
        return $this->getGenericTags()
            ->merge($this->getSocialTags())
            ->merge($this->getTwitterTags());
    }

    /**
     * Devuelve todas las metaetiquetas del contenido en formato HTML.
     */
    public function getHtmlGenericMetatags(): string
    {
        $tags = $this->getGenericTags();

        $html = '';

        $tags->each(function ($value, $key) use (&$html) {
            $html .= '<meta property="'.$key.'" content="'.$value.'">';
        });

        return $html;
    }

    /**
     * Devuelve todas las metaetiquetas para Redes Sociales en formato HTML.
     */
    public function getHtmlMetatagsOpenGraph(): string
    {
        $tags = $this->getSocialTags();

        $html = '';

        $tags->each(function ($value, $key) use (&$html) {
            $html .= '<meta property="'.$key.'" content="'.$value.'">';
        });

        return $html;
    }

    /**
     * Devuelve todas las metaetiquetas para Twitter en formato HTML.
     */
    public function getHtmlMetatagsTwitter(): string
    {
        $tags = $this->getTwitterTags();

        $html = '';

        $tags->each(function ($value, $key) use (&$html) {
            $html .= '<meta property="'.$key.'" content="'.$value.'">';
        });

        return $html;
    }

    /**
     * Devuelve todas las metaetiquetas del contenido en formato HTML.
     */
    public function getHtmlMetatags(): string
    {
        return $this->getGenericTags().$this->getHtmlMetatagsOpenGraph().$this->getHtmlMetatagsTwitter();
    }
}
