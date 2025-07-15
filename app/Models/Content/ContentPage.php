<?php

namespace App\Models\Content;

use App\Helpers\TextFormatParseHelper;
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
     * Obtiene el contenido según el tipo especificado
     *
     * @param string $type
     * @return mixed
     */
    public function getContentByType(string $type = 'html'): string
    {
        if (!$type || $type === 'html') {
            return $this->content;
        }

        $contentRaw = ContentPageRaw::where('content_page_raw.content_page_id', $this->id)
            ->leftJoin('content_available_page_raw', 'content_available_page_raw.id', '=', 'content_page_raw.available_page_raw_id')
            ->where('content_available_page_raw.type', $type)
            ->first();

        return $contentRaw?->content ?? $this->content;
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

    /**
     * Elimina de forma segura la página y cualquier elemento asociado, incluso del storage.
     *
     * @return bool
     */
    public function safeDelete(): bool
    {
        $content = $this->contentModel;

        ## Contenido en bruto asociado a esta página.
        $raws = ContentPageRaw::where('content_page_id', $this->id)->get();

        foreach ($raws as $raw) {

            ## Cuando es un JSON, proviene del editor.js
            if ($raw->available_page_raw_id === 2) {
                $jsonRaw = json_decode($raw->content, true);

                $blocks = $jsonRaw['blocks'] ?? null;

                if ($blocks && count($blocks)) {
                    $blocksToDelete = TextFormatParseHelper::searchBlocks($blocks, [
                        'attaches',
                        'image'
                    ]);

                    $filesId = $blocksToDelete->pluck('data.file.file_id')->toArray();
                    $contentFilesId = $blocksToDelete->pluck('data.file.content_file_id')->toArray();

                    $files = File::whereIn('id', $filesId)->get();

                    foreach ($files as $f) {
                        $f->safeDelete();
                    }

                    ContentFile::whereIn('id', $contentFilesId)->delete();
                }
            }
        }

        ## Borro la imagen principal de la página.
        $this->image?->safeDelete();

        ## Reordeno todas las páginas.
        $content->pages()->where('order', '>', $this->order)->get()->map(function ($page) {
            --$page->order;
            $page->save();
        });

        return $this->delete();
    }


    /**
     * Limpia una cadena de texto, elimina html, entidades y espacios innecesarios.
     * También capitaliza la primera letra del texto.
     *
     * @param string $text Cadena de texto a limpiar.
     *
     * @return string
     */
    public static function sanitizeTitle(string $text): string
    {
        $title = trim(str_replace(['&amp;', '&nbsp;', '&#160;', '<p>', '<br>'], '', $text));
        $title = trim(strip_tags(html_entity_decode($title)));
        $title = trim(str_replace(['&amp;', '&nbsp;', '&#160;', '<p>', '<br>'], '', $title));
        $title = trim(strip_tags(html_entity_decode(preg_replace("/&#?[a-z0-9]+;/i",'',$title))));
        $title = trim(preg_replace("/\s+/", ' ', $title));

        return ucfirst($title);
    }
}
