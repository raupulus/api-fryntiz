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

    public function index(User $user)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function store(User $user, HardwareDevice $hardwareDevice)
    {
        return true;
    }

    public function delete(User $user, HardwareDevice $hardwareDevice)
    {
        return true;
    }

    public function show(User $user, HardwareDevice $hardwareDevice)
    {
        return true;
    }

    public function update(User $user, HardwareDevice $hardwareDevice)
    {
        return true;
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
