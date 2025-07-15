<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentMetadataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return array_filter([
            'web' => $this->web,
            'telegram_channel' => $this->telegram_channel,
            'youtube_channel' => $this->youtube_channel,
            'youtube_video' => $this->youtube_video,
            'youtube_video_id' => $this->youtube_video_id,
            'gitlab' => $this->gitlab,
            'github' => $this->github,
            'mastodon' => $this->mastodon,
            'twitter' => $this->urlTwitter,
        ]);
    }
}
