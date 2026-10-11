<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Models\Concerns\BumpsApiCache;
use App\Models\Content\ContentCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
class PlatformCategory extends BaseModel
{
    use BumpsApiCache;

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

    /**
     * Relación con la plataforma a la que pertenece.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    /**
     * Los contenidos enlazados a esta categoría de la plataforma. Borrar la
     * fila de la pivote los borra también (clave foránea en cascada).
     */
    public function contentCategories(): HasMany
    {
        return $this->hasMany(ContentCategory::class, 'platform_category_id');
    }
}
