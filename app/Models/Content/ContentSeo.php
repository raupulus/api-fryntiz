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
 * @package App\Models\Content
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
     *
     * @return BelongsTo
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Relación con el autor del contenido.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->content->author();
    }

    /**
     * Relación con la imagen asociada al seo.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }


    /**
     * Devuelve todas las etiquetas genéricas.
     *
     * @return Collection
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
     *
     * @return Collection
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
            //'og:image:type' => '', // TODO: Obtener desde relación con Files
            //'og:image:width' => '', // TODO: Obtener desde relación con Files
            //'og:image:height' => '', // TODO: Obtener desde relación con Files
            'og:type' => $this->og_type,
            'og:description' => $this->description,
        ]);
    }

    /**
     * Devuelve todas las etiquetas de Twitter.
     *
     * @return Collection
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
     *
     * @return Collection
     */
    public function getMetaTags(): Collection
    {
        return $this->getGenericTags()
            ->merge($this->getSocialTags())
            ->merge($this->getTwitterTags());
    }

    /**
     * Devuelve todas las metaetiquetas del contenido en formato HTML.
     *
     * @return string
     */
    public function getHtmlGenericMetatags(): string
    {
        $tags = $this->getGenericTags();

        $html = '';

        $tags->each(function ($value, $key) use (&$html) {
            $html .= '<meta property="' . $key . '" content="' . $value . '">';
        });

        return $html;
    }

    /**
     * Devuelve todas las metaetiquetas para Redes Sociales en formato HTML.
     *
     * @return string
     */
    public function getHtmlMetatagsOpenGraph(): string
    {
        $tags = $this->getSocialTags();

        $html = '';

        $tags->each(function ($value, $key) use (&$html) {
            $html .= '<meta property="' . $key . '" content="' . $value . '">';
        });

        return $html;
    }

    /**
     * Devuelve todas las metaetiquetas para Twitter en formato HTML.
     *
     * @return string
     */
    public function getHtmlMetatagsTwitter(): string
    {
        $tags = $this->getTwitterTags();

        $html = '';

        $tags->each(function ($value, $key) use (&$html) {
            $html .= '<meta property="' . $key . '" content="' . $value . '">';
        });

        return $html;
    }

    /**
     * Devuelve todas las metaetiquetas del contenido en formato HTML.
     *
     * @return string
     */
    public function getHtmlMetatags(): string
    {
        return $this->getGenericTags() . $this->getHtmlMetatagsOpenGraph() . $this->getHtmlMetatagsTwitter();
    }
}
