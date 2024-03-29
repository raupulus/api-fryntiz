<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentAvailableStatus
 */
class ContentAvailablePageRaw extends BaseModel
{
    use HasFactory;

    protected $table = 'content_available_page_raw';
}
