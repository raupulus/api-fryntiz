<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentRelated
 */
class ContentPageRaw extends BaseModel
{
    use HasFactory;

    protected $table = 'content_page_raw';

    protected $fillable = [
        'content_page_id',
        'available_page_raw_id',
        'content'
    ];
}
