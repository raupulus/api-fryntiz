<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentFeaturedResource extends JsonResource
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
            ];
        }

        return [
            'title' => $this->title,
            'type' => $this->type?->slug,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'has_image' => (bool) $this->image_id,
            'images' => $images,
            'updated_at' => $this->updated_at,
            'categories' => $this->categoriesQuery()->select(['categories.name', 'categories.slug'])->get(),
            'subcategories' => $this->subcategoriesQuery()->select(['categories.name', 'categories.slug', 'content_categories.is_main', 'parent_id'])->get()->map(function ($subcat) {
                $subcat->parent = $subcat->parentCategory?->slug;
                unset($subcat->parent_id);
                unset($subcat->parentCategory);

                return $subcat;
            }),
        ];
    }
}
