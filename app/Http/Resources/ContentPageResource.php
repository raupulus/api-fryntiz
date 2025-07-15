<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentPageResource extends JsonResource
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
                'medium' => $this->urlImageMedium,
                'normal' => $this->urlImageNormal,
                'large' => $this->urlImageLarge,
            ];
        }

        ## Obtener el tipo de contenido desde additional data o desde el request
        $contentType = $this->additional['content_type'] ?? $request->get('content_type') ?? 'html';

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->getContentByType($contentType),
            'order' => $this->order,
            'images' => $images,
            'has_image' => (bool) $this->image_id,
        ];
    }
}
