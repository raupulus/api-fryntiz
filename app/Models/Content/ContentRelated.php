<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentRelated
 */
class ContentRelated extends BaseModel
{
    use HasFactory;

    protected $table = 'content_related';

    protected $fillable = [
        'content_id',
        'content_related_id'
    ];
}
