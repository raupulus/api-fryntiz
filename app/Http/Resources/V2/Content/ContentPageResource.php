<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Enums\ContentPageFormatEnum;
use App\Models\Content\ContentPage;
use App\Services\Content\ContentPageFormatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para páginas de contenido en API V2.
 *
 * `body` sale en el formato que dice `format`: el pedido con `?format=` o, sin
 * él, el de la página (`source_format`, el que se edita en el panel). HTML y
 * Markdown son texto; Editor.js es el objeto `{time, blocks, version}` tal cual.
 * `ContentPagesRequest` valida `?format=` antes de llegar aquí.
 *
 * @mixin ContentPage
 */
class ContentPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(ContentPageFormatService::class);

        $source = $service->sourceFormat($this->resource);
        $format = ContentPageFormatEnum::tryFrom((string) $request->query('format')) ?? $source;
        $body = $service->contentIn($this->resource, $format);

        return [
            'id' => $this->id,
            'content_id' => $this->content_id,
            'order' => $this->order,
            'title' => $this->title,
            'format' => $format->value,
            'source_format' => $source->value,
            // La columna es `content`, no `body` (**N219**): `body` es el nombre
            // que ya usaban las webs y ahora lleva el formato de `format`.
            'body' => $format === ContentPageFormatEnum::EditorJs
                ? (json_decode($body, true) ?? ['blocks' => []])
                : $body,
            'slug' => $this->slug,
            // `raw_type` no era columna: la tabla tiene `current_page_raw_id`.
            'current_page_raw_id' => $this->current_page_raw_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
