<?php

namespace App\Policies;

use App\Models\Keycounter\Keyboard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class KeyCounterKeyboardPolicy
 * @package App\Policies
 */
class KeyCounterKeyboardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Keycounter\Keyboard  $keyboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Keyboard $keyboard)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Keycounter\Keyboard  $keyboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Keyboard $keyboard)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Keycounter\Keyboard  $keyboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Keyboard $keyboard)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Keycounter\Keyboard  $keyboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Keyboard $keyboard)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Keycounter\Keyboard  $keyboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Keyboard $keyboard)
    {
        //
    }
}
