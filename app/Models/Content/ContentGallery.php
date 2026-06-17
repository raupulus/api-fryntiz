<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentGallery
 *
 * @property int $id
 * @property int|null $gallery_id FK al a la galería que pertenezca
 * @property int|null $content_id FK al contenido que se asocia con la galería
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereGalleryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentGallery whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContentGallery extends BaseModel
{
    use HasFactory;
}
