<?php

declare(strict_types=1);

namespace App\Http\Resources\V2;

use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Platform
 */
class PlatformResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // La columna es `title`, no `name` (**N1**). Se mantienen las dos
            // claves: `name` es la que consumen las webs.
            'name' => $this->title,
            'title' => $this->title,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'description' => $this->description,
            // Igual que en los contenidos: la imagen se sirve como recurso, no
            // como el modelo `File` entero, y sólo si viene cargada. Devolver
            // `$this->image` a secas era una consulta perdida por fila y, con
            // `preventLazyLoading`, un 500 en el listado.
            'image' => $this->whenLoaded('image', fn () => new SocialImageResource($this->image)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
