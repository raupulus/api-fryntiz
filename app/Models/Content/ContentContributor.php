<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentGallery
 *
 * @package App\Models\Content
 */
class ContentContributor extends BaseModel
{
    use HasFactory;

    protected $table = 'content_contributors';

    protected $fillable = [
        'content_id',
        'user_id',
    ];
}
