<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\PlatformCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Content\ContentCategory
 */
class ContentCategory extends BaseModel
{
    protected $table = 'content_categories';

    protected $fillable = [
        'content_id',
        'platform_category_id',
        'is_main'
    ];

    /**
     * Define a many-to-one relationship with the PlatformCategory model.
     *
     * @return BelongsTo
     */
    public function platformCategory(): BelongsTo
    {
        return $this->belongsTo(PlatformCategory::class, 'platform_category_id');
    }
}
