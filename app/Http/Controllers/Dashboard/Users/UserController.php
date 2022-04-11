<?php

namespace App\Http\Controllers\Dashboard\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Class UserController
 */
class UserController extends Controller
{
    public function index()
    {
        ## Usuarios Activos.
        $n_usersActive = User::countActive();

        ## Usuarios nuevos de este mes
        $n_users_this_month = User::countNewInThisMonth();

        ## Usuarios Inactivos (SoftDelete)
        $n_usersInactive = User::countInactive();

        $n_users = $n_usersActive + $n_usersInactive;

        return view('dashboard.users.index')->with([
            'users' => User::all(), // TEMPORAL, traer por ajax
            'n_users' => $n_users,
            //'usersInactive' => $usersInactive,
            'n_usersActive' => $n_usersActive,
            'n_usersInactive' => $n_usersInactive,
            'n_users_this_month' => $n_users_this_month,
        ]);
    }
}
