<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Http\Resources\V2\SocialImageResource;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una galería del contenido con sus fotos, en su orden. Necesita
 * `image.fileType`, `image.thumbnails` e `images.image.fileType|thumbnails`.
 *
 * @mixin Gallery
 */
class ContentGalleryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'aspect_ratio' => $this->aspect_ratio?->value,
            'cover' => $this->image === null ? null : new SocialImageResource($this->image),
            'images' => $this->images
                ->sortBy('order')
                ->values()
                ->map(fn (GalleryImage $image): array => [
                    'order' => $image->order,
                    'caption' => $image->caption,
                    'image' => $image->image === null ? null : (new SocialImageResource($image->image))->resolve($request),
                ])
                ->all(),
        ];
    }
}
