<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;

/**
 * Class ContentPage
 */
class ContentPage extends BaseModel
{
    protected $table = 'content_pages';

    protected $fillable = [
        'content_id',
        'title',
        'slug',
        'content',
        'order',
    ];
}
