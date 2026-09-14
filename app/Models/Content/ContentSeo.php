<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Content $content
 * @property-read string $url_image
 * @property-read string $url_image_large
 * @property-read string $url_image_medium
 * @property-read string $url_image_micro
 * @property-read string $url_image_normal
 * @property-read string $url_image_small
 * @property-read File|null $image
 *
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
 *
 * @mixin \Eloquent
 */
class ContentSeo extends BaseModel
{
    use ImageTrait;
    use SoftDeletes;

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
     * Relación con la imagen asociada al seo.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }
}
