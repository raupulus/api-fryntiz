<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $platform_id Relación con la plataforma
 * @property int $category_id Relación con la categoría
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Category $category
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory wherePlatformId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PlatformCategory extends Model
{
    protected $fillable = [
        'platform_id',
        'category_id',
    ];

    /**
     * Relación con la tabla de categorías para las plataformas.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
