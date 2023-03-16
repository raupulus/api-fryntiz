<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Content\ContentTag
 */
class ContentTag extends BaseModel
{
    use HasFactory;

    protected $table = 'content_tags';

    protected $fillable = [
        'content_id',
        'platform_tag_id',
    ];
}
