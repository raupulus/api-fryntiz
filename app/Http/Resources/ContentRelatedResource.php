<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentRelatedResource extends JsonResource
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

        if ($this->image) {
            $images = [
                'urlImageSmall' => $this->urlImageSmall,
                'urlImageMedium' => $this->urlImageMedium,
            ];
        }

        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'images' => $images,
            'updated_at' => $this->updated_at,
        ];
    }
}
