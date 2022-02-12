<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use function array_merge;
use function auth;
use function response;

/**
 * Class UserProfileController
 */
class UserProfileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        ## Filtro los datos que serán privados para los usuarios.
        if (!auth()->id() || auth()->id() !== $user->id) {
            $hidden = array_merge($user->getHidden(), [
                'email',
                'password',
                'remember_token',
                'profile_photo_path',
            ]);

            $user->setHidden($hidden);
        }

        return response()->json($user);
    }

    /**
     * Devuelve los datos del perfil para el usuario logueado.
     *
     * @return \Illuminate\Http\Response
     */
    public function myProfile()
    {
        return $this->show(auth()->user());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
