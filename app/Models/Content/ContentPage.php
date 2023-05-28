<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class ContentPage
 */
class ContentPage extends BaseModel
{
    protected $table = 'content_pages';

    protected $fillable = [
        'current_page_raw_id',
        'content_id',
        'title',
        'slug',
        'content',
        'order',
    ];


    /**
     * Relación con el contenido RAW desde el que se genera el código HTML final.
     * En caso de tener varios orígenes, se tomará el actualizado más recientemente.
     *
     * @return HasOne
     */
    public function raw(): HasOne
    {
        return $this->hasOne(ContentPageRaw::class, 'content_page_id', 'id')
            ->orderByDesc('updated_at');
    }


}
