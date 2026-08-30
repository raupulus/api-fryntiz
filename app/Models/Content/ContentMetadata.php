<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Metadatos asociados al contenido.
 *
 * @property int $id
 * @property int|null $content_id FK al contenido asociado
 * @property string|null $web Sitio web del contenido
 * @property string|null $telegram_channel Canal de telegram
 * @property string|null $youtube_channel Canal de Youtube, página principal del canal
 * @property string|null $youtube_video Vídeo en Youtube con presentación o asociado al contenido
 * @property string|null $youtube_video_id ID del vídeo subido en Youtube
 * @property string|null $gitlab Url del repositorio asociado en Gitlab
 * @property string|null $github Url del repositorio asociado en Github
 * @property string|null $mastodon Cuenta de Mastodon
 * @property string|null $twitter Cuenta de Twitter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string|null $url_twitter
 * @property-read string|null $youtube_video_iframe_url
 * @property-read string|null $youtube_video_url
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereGithub($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereGitlab($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereMastodon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereTelegramChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereTwitter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereWeb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereYoutubeChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereYoutubeVideo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentMetadata whereYoutubeVideoId($value)
 *
 * @mixin \Eloquent
 */
class ContentMetadata extends BaseModel
{
    use SoftDeletes;

    protected $table = 'content_metadata';

    protected $fillable = [
        'content_id', 'web', 'telegram_channel', 'youtube_channel', 'youtube_video', 'youtube_video_id', 'gitlab',
        'github', 'mastodon', 'twitter',
    ];

    /**
     * Mantiene sincronizada la URL del vídeo a partir de su ID al guardar.
     */
    protected static function booted(): void
    {
        static::saving(function (ContentMetadata $metadata) {
            if ($metadata->youtube_video_id) {
                $metadata->youtube_video = 'https://www.youtube.com/watch?v='.$metadata->youtube_video_id;
            } elseif (blank($metadata->youtube_video_id)) {
                $metadata->youtube_video = null;
            }
        });
    }

    /**
     * Devuelve la url para insertar el iframe.
     */
    public function getYoutubeVideoIframeUrlAttribute(): ?string
    {
        return $this->youtube_video_id ? 'https://www.youtube.com/embed/'.$this->youtube_video_id : null;
    }

    /**
     * Devuelve la url para ver el vídeo de youtube.
     */
    public function getYoutubeVideoUrlAttribute(): ?string
    {
        return $this->youtube_video_id ? 'https://www.youtube.com/watch?v='.$this->youtube_video_id : null;
    }

    /**
     * Get the URL for the Twitter attribute.
     *
     * @return string|null The URL for the Twitter attribute if it exists, otherwise null.
     */
    public function getUrlTwitterAttribute(): ?string
    {
        return $this->twitter ? 'https://twitter.com/'.$this->twitter : null;
    }
}
