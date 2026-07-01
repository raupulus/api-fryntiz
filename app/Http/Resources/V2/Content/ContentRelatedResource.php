<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\Content\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource ligero para contenido relacionado en API V2.
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
            'image' => $this->image,
            'type' => $this->type,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
