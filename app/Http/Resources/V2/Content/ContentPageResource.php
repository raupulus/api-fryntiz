<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\Content\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para páginas de contenido en API V2.
 *
 * @mixin ContentPage
 */
class ContentPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content_id' => $this->content_id,
            'order' => $this->order,
            'title' => $this->title,
            // La columna es `content`, no `body` (**N219**). Se mantiene la clave
            // `body` en la respuesta porque es la que ya consumen las webs; el
            // renombrado va con el rediseño REST de la fase 5.
            'body' => $this->content,
            'slug' => $this->slug,
            // `raw_type` no era columna: la tabla tiene `current_page_raw_id`.
            'current_page_raw_id' => $this->current_page_raw_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
