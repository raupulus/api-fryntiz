<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Cv;

use App\Models\CV\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un currículum en un listado: lo justo para pintar una tarjeta.
 *
 * No lleva `share_token` ni ninguna de las secciones. El token es la llave del
 * enlace privado: si saliera en el listado público dejaría de ser privado.
 *
 * @mixin Curriculum
 */
class CurriculumSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'presentation' => $this->presentation,
            'is_default' => (bool) $this->is_default,
            'is_downloadable' => (bool) $this->is_downloadable,
            'image' => $this->url_image ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
