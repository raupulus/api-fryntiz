<?php

namespace App\Services\Cv;

use App\Models\CV\Curriculum;
use App\Models\User;

class CurriculumService
{
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
