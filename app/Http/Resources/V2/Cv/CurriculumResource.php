<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Cv;

use App\Models\CV\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un currículum completo con sus secciones.
 *
 * Cada bloque usa `whenLoaded`: si la relación no viene cargada, la clave no
 * aparece — en vez de devolverla vacía, que haría pensar que no hay datos, o
 * de dispararla bajo demanda, que con `preventLazyLoading` revienta y en
 * producción sería una consulta por sección.
 *
 * @mixin Curriculum
 */
class CurriculumResource extends JsonResource
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

            'experiences' => [
                'accredited' => $this->whenLoaded('experienceAccredited'),
                'no_accredited' => $this->whenLoaded('experienceNoAccredited'),
                'self_employed' => $this->whenLoaded('experienceSelfEmployed'),
                'additional' => $this->whenLoaded('experienceAdditional'),
                'other' => $this->whenLoaded('experienceOther'),
            ],
            'educations' => [
                'training' => $this->whenLoaded('academicTraining'),
                'complementary' => $this->whenLoaded('academicComplementary'),
                'complementary_online' => $this->whenLoaded('academicComplementaryOnline'),
            ],
            'skills' => $this->whenLoaded('skills'),
            'projects' => $this->whenLoaded('projects'),
            'repositories' => $this->whenLoaded('repositories'),
            'services' => $this->whenLoaded('services'),
            'collaborations' => $this->whenLoaded('collaborations'),
            'hobbies' => $this->whenLoaded('hobbies'),
            'jobs' => $this->whenLoaded('jobs'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
