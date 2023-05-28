<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'password' => 'required|max:80',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 403);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json($user, 200);
    }

    public function login(LoginRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 403);
        }

        $auth_data = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (Auth::guard('web')->attempt($auth_data)) {
            $user = Auth::guard('web')->user();
            $user->api_token = Str::random(60);
            $user->save();

            return $user;
        }

        return response()->json(['message' => 'Error login credentials', 401]);
    }

    public function logout()
    {
        $user = Auth::guard('api')->user();
        $user->api_token = null;
        $user->save();

        return response()->json(['message' => 'You are successfully logged out'], 200);
    }
}
