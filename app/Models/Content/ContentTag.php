<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * App\Models\Content\ContentTag
 *
 * @property int $id
 * @property int|null $content_id FK al contenido asociado
 * @property int|null $platform_tag_id FK a la plataforma asociada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag wherePlatformTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentTag extends BaseModel
{
    use HasFactory;

    protected $table = 'content_tags';

    protected $fillable = [
        'content_id',
        'platform_tag_id',
    ];
}
