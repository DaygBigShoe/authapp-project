<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private function auth($request) {
        if (!auth()->attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            return response([
                'message' => 'Invalid login credentials'
            ], 400);
        }

        $token = auth()->user()->createToken('userToken')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        return $this->auth($request);
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return $this->auth($request);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return response()->json(auth()->user()->currentAccessToken());
    }
}
