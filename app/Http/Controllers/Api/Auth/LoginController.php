<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class LoginController
 */
class LoginController extends Controller
{
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            //'token' => \auth()->user()->createToken($request->device)->plainTextToken,
            'token' => \auth()->user()->createToken('login')->plainTextToken,
            'message' => 'Success'
        ]);
    }

    public function validateLogin(Request $request)
    {
        return $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            //'device' => 'required'
        ]);
    }
}
