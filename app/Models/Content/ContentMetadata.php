<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Model;

/**
 * Metadatos asociados al contenido.
 */
class ContentMetadata extends BaseModel
{
    protected $table = 'content_metadata';

    /**
     * Devuelve la url para insertar el iframe.
     *
     * @return string|null
     */
    public function getYoutubeVideoIframeUrlAttribute(): ?string
    {
        return $this->youtube_video_id ? 'https://www.youtube.com/embed/' . $this->youtube_video_id : null;
    }

    /**
     * Devuelve la url para ver el vídeo de youtube.
     *
     * @return string|null
     */
    public function getYoutubeVideoUrlAttribute(): ?string
    {
        return $this->youtube_video_id ? 'https://www.youtube.com/watch?v=' . $this->youtube_video_id : null;
    }
}
