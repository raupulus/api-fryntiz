<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\Gallery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class ContentGallery
 *
 * @property int $id
 * @property int|null $gallery_id FK al a la galería que pertenezca
 * @property int|null $content_id FK al contenido que se asocia con la galería
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Content|null $content
 * @property-read Gallery|null $gallery
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereGalleryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentGallery extends BaseModel
{
    use HasFactory;

    /**
     * Contenido asociado.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Galería asociada.
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }
}
