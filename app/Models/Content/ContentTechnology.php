<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contenido asociado con su tecnología/s
 *
 * @property int $id
 * @property int|null $content_id FK al contenido asociado
 * @property int|null $technology_id FK a la tecnología asociada
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Content\Content|null $content
 * @property-read Technology|null $technology
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology whereTechnologyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentTechnology whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContentTechnology extends BaseModel
{
    protected $table = 'content_technologies';

    protected $fillable = ['content_id', 'technology_id'];

    /**
     * Contenido que asocia.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Tecnología asociada.
     */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class, 'technology_id', 'id');
    }
}
