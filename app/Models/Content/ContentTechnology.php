<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contenido asociado con su tecnología/s
 */
class ContentTechnology extends BaseModel
{
    protected $table = 'content_technologies';

    protected $fillable = ['content_id', 'technology_id'];

    /**
     * Contenido que asocia.
     *
     * @return BelongsTo
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Tecnología asociada.
     *
     * @return BelongsTo
     */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class, 'technology_id', 'id');
    }
}
