<?php

namespace App\Models\Content;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class ContentPage
 */
class ContentPage extends BaseModel
{
    use ImageTrait;

    protected $table = 'content_pages';

    protected $fillable = [
        'current_page_raw_id',
        'content_id',
        'image_id',
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

    /**
     * Relación con la imagen principal de la página.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Relación con el contenido al que pertenece la página.
     *
     * @return BelongsTo
     */
    public function contentModel(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Devuelve la ruta para actualizar la imagen de la página.
     *
     * @return string
     */
    public function getUrlStoreImageAttribute(): string
    {
        //return route('content.page.store.image', ['content_page' => $this->id]);
        return route('dashboard.content.ajax.page.upload.image.update', ['contentPage' => $this->id]);
    }
}
