<?php

namespace App\Policies;

use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * Class HardwarePolicy
 */
class HardwarePolicy
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

    /**
     * Permisos para guardar datos de dispositivos solares.
     *
     * @return bool
     */
    public function storeSolarCharge(User $user, HardwareDevice $model)
    {
        //Log::info('Entrando a storeSolarCharge');
        //Log::info('El usuario es: ' . $user->id);
        //Log::info('El modelo es: ' . $model->id);

        /*
        if ($user->role_id == 1) {
            return true;
        }
        */

        return $model->user_id === $user->id;
    }

    /**
     * Permisos para mostrar datos de dispositivos solares.
     *
     * @return bool
     */
    public function indexSolarCharge()
    {
        return true;
    }
}
