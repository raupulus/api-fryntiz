<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para páginas de contenido en API V2.
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
            'body' => $this->body,
            'raw_type' => $this->raw_type,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
