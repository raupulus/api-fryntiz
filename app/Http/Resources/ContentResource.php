<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $images = null;

        if ($this->image_id) {
            $images = [
                'small' => $this->urlImageSmall,
                'medium' => $this->urlImageMedium,
                'large' => $this->urlImageNormal,
            ];
        }

        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type?->slug,
            'excerpt' => $this->excerpt,
            'is_featured' => $this->is_featured,
            'images' => $images,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_human' => $this->created_at->translatedFormat('d F Y'),
            'total_pages' => $this->pages()->count(),
            'categories' => $this->categoriesQuery()->select(['categories.name', 'categories.slug'])->get(),
            'subcategories' => $this->subcategoriesQuery()->select(['categories.name', 'categories.slug', 'content_categories.is_main', 'parent_id'])->get()->map(function ($subcat) {
                $subcat->parent = $subcat->parentCategory?->slug;
                unset($subcat->parent_id);
                unset($subcat->parentCategory);

                return $subcat;
            }),
            'tags' => $this->tagsQuery()->pluck('name'),
            'technologies' => $this->technologies->map(function ($tech) {
                return [
                    'name' => $tech->name,
                    'slug' => $tech->slug,
                    'urlImageSmall' => $tech->urlImageSmall,
                ];
            }),
            'pages_slug' => $this->pages()->pluck('slug'),
            'metadata' => ContentMetadataResource::make($this->whenLoaded('metadata')),
            'has_image' => (bool) $this->image_id,
        ];
    }
}
