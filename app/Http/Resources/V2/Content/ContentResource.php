<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\Content\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para contenido en API V2.
 *
 * @mixin Content
 */
class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'type' => $this->type,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'image' => $this->image,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'platform' => $this->whenLoaded('platform', fn () => [
                'id' => $this->platform->id,
                'name' => $this->platform->title,
                'slug' => $this->platform->slug,
            ]),
            'categories' => $this->whenLoaded('categories'),
            'tags' => $this->whenLoaded('tags'),
            'technologies' => $this->whenLoaded('technologies'),
            'pages_count' => $this->whenCounted('pages'),
            'views_count' => $this->views_count ?? 0,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
