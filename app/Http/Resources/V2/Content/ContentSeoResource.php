<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Http\Resources\V2\SocialImageResource;
use App\Models\Content\ContentSeo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El SEO de un contenido (E5), con la imagen para redes. Necesita
 * `image.fileType` e `image.thumbnails`.
 *
 * @mixin ContentSeo
 */
class ContentSeoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'keywords' => $this->keywords,
            'robots' => $this->robots,
            'revisit_after' => $this->revisit_after,
            'distribution' => $this->distribution,
            'og_title' => $this->og_title,
            'og_type' => $this->og_type,
            'twitter_card' => $this->twitter_card,
            'twitter_creator' => $this->twitter_creator,
            'image' => $this->image === null ? null : new SocialImageResource($this->image),
            'image_alt' => $this->image_alt,
        ];
    }
}
