<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\Content\ContentMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Los enlaces de un contenido («Vídeo y enlaces» de la ficha).
 *
 * @mixin ContentMetadata
 */
class ContentMetadataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'web' => $this->web,
            'youtube_video' => $this->youtube_video,
            'youtube_video_id' => $this->youtube_video_id,
            'youtube_channel' => $this->youtube_channel,
            'github' => $this->github,
            'gitlab' => $this->gitlab,
            'telegram_channel' => $this->telegram_channel,
            'mastodon' => $this->mastodon,
            'twitter' => $this->twitter,
        ];
    }
}
