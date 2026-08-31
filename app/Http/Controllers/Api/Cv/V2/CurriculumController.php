<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cv\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\Cv\CurriculumResource;
use App\Http\Resources\V2\Cv\CurriculumSummaryResource;
use App\Models\CV\Curriculum;
use App\Services\Cv\CurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Currículums.
 *
 * El módulo tenía dieciocho tablas montadas para poder tener varios CV y una
 * API que sólo sabía devolver «el del superadmin»: `/cv/experience` daba por
 * hecho que hay uno solo. Ahora cada sección cuelga de su CV.
 *
 * Visibilidad (B1): privado, compartido por enlace, o público. El listado sólo
 * enseña los públicos; el compartido se sirve por su token y con `noindex`.
 */
class CurriculumController extends BaseApiController
{
    /**
     * Secciones que se pueden pedir sueltas y de qué relación salen.
     *
     * `experiences` y `educations` agrupan varias tablas porque para quien lee
     * un CV son una sola cosa.
     *
     * @var array<string, array<int, string>>
     */
    private const SECTIONS = [
        'experiences' => [
            'experienceAccredited', 'experienceNoAccredited',
            'experienceSelfEmployed', 'experienceAdditional', 'experienceOther',
        ],
        'educations' => [
            'academicTraining', 'academicComplementary', 'academicComplementaryOnline',
        ],
        'skills' => ['skills'],
        'projects' => ['projects'],
        'repositories' => ['repositories'],
        'services' => ['services'],
        'collaborations' => ['collaborations'],
        'hobbies' => ['hobbies'],
        'jobs' => ['jobs'],
    ];

    public function __construct(private readonly CurriculumService $service) {}

    /**
     * Listado público. Sólo los marcados como públicos (B3).
     */
    public function index(Request $request): JsonResponse
    {
        $query = new CollectionQuery(
            filterable: ['created_at'],
            sortable: ['title', 'created_at'],
            defaultSortColumn: 'title',
            defaultSortDescending: false,
        );

        return $this->paginatedResponse(
            $query->paginate(Curriculum::query()->publicOnly(), $request),
            CurriculumSummaryResource::class
        );
    }

    /**
     * Un CV completo por su slug. Sólo si es público.
     */
    public function show(string $slug): JsonResponse
    {
        $cv = $this->service->bySlug($slug);

        // Mismo 404 si no existe que si es privado: la URL no confirma que
        // exista un CV con ese nombre.
        if (! $cv || ! $cv->isVisibleTo()) {
            return $this->notFoundResponse('Currículum no encontrado');
        }

        return $this->successResponse(new CurriculumResource($cv));
    }

    /**
     * Un CV por su enlace privado.
     *
     * La respuesta lleva `X-Robots-Tag: noindex` para que no acabe en Google si
     * alguien pega el enlace en cualquier sitio.
     */
    public function shared(string $shareToken): JsonResponse
    {
        $cv = $this->service->byShareToken($shareToken);

        if (! $cv) {
            return $this->notFoundResponse('Currículum no encontrado');
        }

        return $this->successResponse(new CurriculumResource($cv))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Una sección concreta de un CV.
     */
    public function section(string $slug, string $section): JsonResponse
    {
        if (! isset(self::SECTIONS[$section])) {
            return $this->notFoundResponse('Sección no reconocida');
        }

        $cv = $this->service->bySlug($slug);

        if (! $cv || ! $cv->isVisibleTo()) {
            return $this->notFoundResponse('Currículum no encontrado');
        }

        $data = [];

        foreach (self::SECTIONS[$section] as $relation) {
            $data[$this->keyFor($relation)] = $cv->{$relation};
        }

        // Una sección de una sola relación se devuelve como lista, no como
        // objeto con una clave: `/skills` devuelve las habilidades, no
        // `{skills: [...]}`.
        return $this->successResponse(count($data) === 1 ? reset($data) : $data);
    }

    /**
     * `experienceAccredited` → `accredited`, `academicTraining` → `training`.
     */
    private function keyFor(string $relation): string
    {
        $withoutPrefix = preg_replace('/^(experience|academic)/', '', $relation) ?: $relation;

        return Str::snake(lcfirst($withoutPrefix));
    }
}
