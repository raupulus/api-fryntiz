<?php

namespace App\Policies;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * Class SmartPlantRegisterPolicy
 */
class SmartPlantRegisterPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function create(User $user,
                           SmartPlantPlant $smartPlantPlant)
    {

        // TODO → Crear sistema de permisos

        return $user->id === $smartPlantPlant->user_id;
    }
}
