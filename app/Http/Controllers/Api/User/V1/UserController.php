<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\CreateRequest;
use App\Http\Requests\Api\User\IndexRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JsonHelper;

/**
 * Class UserController
 * @package App\Http\Controllers\Api\User
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Api\User\IndexRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexRequest $request)
    {
        //$users = User::all(['id', 'name', 'surname', 'created_at']);
        $users = UserResource::collection(User::all());

        return JsonHelper::success(['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param \App\Http\Requests\Api\User\CreateRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        //$newToken = $user->createToken('login');

        // TODO → Enviar email de confirmación al usuario

        return JsonHelper::success([
            'message' => 'User created successfully',
            'user' => $user,
            //'password' => $request->password,
            //'token' => $newToken->plainTextToken,
        ]);
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
