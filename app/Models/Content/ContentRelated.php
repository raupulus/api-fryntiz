<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentRelated
 *
 * @property int $id
 * @property int|null $content_id FK al contenido desde el que se asocia
 * @property int|null $content_related_id FK al contenido asociado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated whereContentRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentRelated whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContentRelated extends BaseModel
{
    use HasFactory;

    protected $table = 'content_related';

    protected $fillable = [
        'content_id',
        'content_related_id',
    ];
}
