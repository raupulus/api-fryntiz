<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ContentGallery
 */
class ContentFile extends BaseModel
{
    use HasFactory;

    protected $table = 'content_files';

    protected $fillable = [
        'content_id',
        'file_id',
    ];
}
