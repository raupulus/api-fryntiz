<?php

declare(strict_types=1);

namespace App\Services\Cv;

use App\Models\CV\Curriculum;
use App\Models\User;

/**
 * Servicio encargado de gestionar las distintas entidades que componen un Currículum Vitae.
 */
class CurriculumService
{
    /**
     * Obtiene el Currículum completo de un usuario, cargando ansiosamente (eager loading) 
     * todas sus relaciones y secciones asociadas (experiencia, proyectos, educación, habilidades...).
     *
     * @param \App\Models\User $user Instancia del usuario.
     * @return \App\Models\CV\Curriculum|null Modelo Curriculum instanciado o null si no posee.
     */
    public function getFullCurriculum(User $user): ?Curriculum
    {
        return Curriculum::with([
            'repositories', 'services', 'collaborations', 'hobbies',
            'jobs', 'projects', 'academicTraining',
            'academicComplementary', 'academicComplementaryOnline',
            'experienceAccredited', 'experienceNoAccredited',
            'experienceSelfEmployed', 'experienceAdditional',
            'experienceOther', 'skills',
        ])->where('user_id', $user->id)->first();
    }
}
