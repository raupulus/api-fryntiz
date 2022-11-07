<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Content\ContentCategory
 */
class ContentCategory extends BaseModel
{
    use HasFactory;

    protected $table = 'content_categories';

    protected $fillable = [
        'content_id',
        'platform_category_id',
    ];
}
