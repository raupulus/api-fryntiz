<?php

declare(strict_types=1);

namespace App\Http\Resources\V2;

use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una tecnología (de un contenido o de los proyectos de una plataforma).
 * Necesita `image.fileType` e `image.thumbnails`.
 *
 * @mixin Technology
 */
class TechnologyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'color' => $this->color,
            'image' => $this->image?->thumbnail('small'),
        ];
    }
}
