<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCategory extends Model
{
    protected $fillable = [
        'platform_id',
        'category_id',
    ];

    /**
     * Relación con la tabla de categorías para las plataformas.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
