<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;

/**
 * Metadatos asociados al contenido.
 */
class ContentMetadata extends BaseModel
{
    protected $table = 'content_metadata';

    protected $fillable = [
        'content_id', 'web', 'telegram_channel', 'youtube_channel', 'youtube_video', 'youtube_video_id', 'gitlab',
        'github', 'mastodon', 'twitter',
    ];

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

    /**
     * Get the URL for the Twitter attribute.
     *
     * @return string|null The URL for the Twitter attribute if it exists, otherwise null.
     */
    public function getUrlTwitterAttribute(): ?string
    {
        return $this->twitter ? 'https://twitter.com/' . $this->twitter : null;
    }
}
