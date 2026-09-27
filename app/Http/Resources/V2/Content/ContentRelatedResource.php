<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Http\Resources\V2\SocialImageResource;
use App\Models\Content\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un contenido en una lista corta (relacionados, destacados, últimos y
 * tendencia). Necesita `type`, `image.fileType` e `image.thumbnails`.
 *
 * @mixin Content
 */
class ContentRelatedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            // Antes, el modelo `File` y el tipo enteros, tal cual.
            'image' => $this->image === null ? null : new SocialImageResource($this->image),
            'type' => $this->type === null ? null : ['id' => $this->type->id, 'slug' => $this->type->slug, 'name' => $this->type->name],
            'is_featured' => (bool) $this->is_featured,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
