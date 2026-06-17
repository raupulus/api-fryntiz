<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentGallery
 *
 * @property int $id
 * @property int|null $content_id FK al contenido asociado
 * @property int|null $user_id FK al usuario asociado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentContributor whereUserId($value)
 * @mixin \Eloquent
 */
class ContentContributor extends BaseModel
{
    use HasFactory;

    protected $table = 'content_contributors';

    protected $fillable = [
        'content_id',
        'user_id',
    ];
}
