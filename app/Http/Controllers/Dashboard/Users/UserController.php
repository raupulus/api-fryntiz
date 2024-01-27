<?php

namespace App\Http\Controllers\Dashboard\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class UserController
 */
class UserController extends Controller
{
    /**
     * Listado de usuarios.
     *
     * @return View
     */
    public function index(): View
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

    /**
     * Muestra un usuario concreto.
     *
     * @param User|null $user
     * @return View
     */
    public function show(User $user = null): View
    {
        $user = $user?->id ? $user : auth()->user();

        return view('dashboard.users.show')->with([
            'user' => $user,
        ]);
    }

    /**
     * Lleva a la vista para crear un usuario.
     *
     * @return View
     */
    public function create(): View
    {
        return view('dashboard.users.add-edit', ['user' => new User()]);
    }

    /**
     * Lleva a la vista para editar usuario.
     *
     * @param User $user
     * @return View
     */
    public function edit(User $user): View
    {
        return view('dashboard.users.add-edit')->with([
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {
        dd('WORK IN PROGRESS');
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
