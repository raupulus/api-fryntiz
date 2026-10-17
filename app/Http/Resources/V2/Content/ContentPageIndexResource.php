<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Http\Resources\V2\SocialImageResource;
use App\Models\Content\ContentPage;
use App\Services\Content\ContentPageFormatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una página en el índice del detalle: sin su texto, que se pide aparte o con
 * `?include=pages`. Necesita `currentRawType` (el formato) y la portada con sus
 * miniaturas; no lee `content`.
 *
 * @mixin ContentPage
 */
class ContentPageIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image === null ? null : new SocialImageResource($this->image),
            'format' => app(ContentPageFormatService::class)->sourceFormatFromType($this->resource)->value,
        ];
    }
}
