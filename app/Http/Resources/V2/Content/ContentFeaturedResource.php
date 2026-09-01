<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\Category;
use App\Models\Content\Content;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Content
 */
class ContentFeaturedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
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
            // API-02: esto accedía a `$subcat->parentCategory?->slug` dentro
            // del map, o sea una consulta por subcategoría, y además mutaba el
            // modelo para colar el slug del padre y borrar el parent_id — de
            // ahí salían los dos errores que había en el baseline de PHPStan.
            // Con el eager load la relación viene resuelta, y devolviendo un
            // array no hay nada que mutar. El JSON es el mismo.
            'subcategories' => $this->subcategoriesQuery()
                ->with('parentCategory:id,slug')
                ->select(['categories.id', 'categories.name', 'categories.slug', 'content_categories.is_main', 'categories.parent_id'])
                ->get()
                ->map(fn (Category $subcat): array => [
                    'name' => $subcat->name,
                    'slug' => $subcat->slug,
                    // `is_main` no es columna de categories: llega del join con
                    // content_categories, así que se lee como atributo suelto.
                    'is_main' => $subcat->getAttribute('is_main'),
                    'parent' => $subcat->parentCategory?->slug,
                ]),
        ];
    }
}
