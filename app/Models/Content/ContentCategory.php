<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\PlatformCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Content\ContentCategory
 *
 * @property int $id
 * @property int|null $content_id FK al contenido asociado
 * @property int|null $platform_category_id FK a la plataforma asociada
 * @property bool $is_main
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read PlatformCategory|null $platformCategory
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory wherePlatformCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentCategory extends BaseModel
{
    protected $table = 'content_categories';

    protected $fillable = [
        'content_id',
        'platform_category_id',
        'is_main',
    ];

    /**
     * Define a many-to-one relationship with the PlatformCategory model.
     */
    public function platformCategory(): BelongsTo
    {
        return $this->belongsTo(PlatformCategory::class, 'platform_category_id');
    }
}
