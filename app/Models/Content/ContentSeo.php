<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentSeo
 *
 * @package App\Models\Content
 */
class ContentSeo extends BaseModel
{
    use HasFactory;

    protected $table = 'content_seo';
}
