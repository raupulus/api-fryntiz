<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cv\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\CV\Curriculum;
use App\Models\User;
use App\Services\Cv\CurriculumService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de CV para API V2.
 * Expone las secciones del currículum vitae como endpoints públicos de lectura.
 */
class CvController extends BaseApiController
{
    public function __construct(private CurriculumService $service) {}

    /**
     * Devuelve el CV completo del usuario principal (superadmin).
     */
    public function index(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv);
    }

    /**
     * Devuelve la sección de experiencia laboral.
     */
    public function experience(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse([
            'accredited' => $cv->experienceAccredited,
            'no_accredited' => $cv->experienceNoAccredited,
            'self_employed' => $cv->experienceSelfEmployed,
            'additional' => $cv->experienceAdditional,
            'other' => $cv->experienceOther,
        ]);
    }

    /**
     * Devuelve la sección de formación académica.
     */
    public function education(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse([
            'academic_training' => $cv->academicTraining,
            'complementary' => $cv->academicComplementary,
            'complementary_online' => $cv->academicComplementaryOnline,
        ]);
    }

    /**
     * Devuelve la sección de habilidades.
     */
    public function skills(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->skills);
    }

    /**
     * Devuelve la sección de proyectos.
     */
    public function projects(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->projects);
    }

    /**
     * Devuelve la sección de repositorios.
     */
    public function repositories(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->repositories);
    }

    /**
     * Devuelve la sección de servicios ofrecidos.
     */
    public function services(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->services);
    }

    /**
     * Devuelve la sección de colaboraciones.
     */
    public function collaborations(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->collaborations);
    }

    /**
     * Devuelve la sección de hobbies.
     */
    public function hobbies(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->hobbies);
    }

    /**
     * Devuelve la sección de trabajos.
     */
    public function jobs(): JsonResponse
    {
        $cv = $this->getMainCurriculum();

        if (! $cv) {
            return $this->notFoundResponse('Curriculum no encontrado');
        }

        return $this->successResponse($cv->jobs);
    }

    /**
     * Obtiene el currículum del usuario principal (superadmin, role_id=1).
     */
    private function getMainCurriculum(): ?Curriculum
    {
        $user = User::where('role_id', 1)->first();

        if (! $user) {
            return null;
        }

        return $this->service->getFullCurriculum($user);
    }
}
