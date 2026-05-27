<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
