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

    public function show(User $user)
    {
        return view('dashboard.users.show')->with([
            'user' => $user,
        ]);
    }

    public function create()
    {
        return view('dashboard.users.add-edit');
    }

    public function edit(User $user)
    {
        return view('dashboard.users.add-edit')->with([
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->all());

        return redirect()->route('dashboard.users.show', $user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('dashboard.users.index');
    }
}
