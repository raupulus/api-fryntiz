<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $platform_id Relación con la plataforma
 * @property int $tag_id Relación con la etiqueta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Tag $tag
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag wherePlatformId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformTag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PlatformTag extends Model
{
    protected $fillable = [
        'platform_id',
        'tag_id',
    ];

    /**
     * Relación con la tabla de etiquetas para las plataformas.
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
