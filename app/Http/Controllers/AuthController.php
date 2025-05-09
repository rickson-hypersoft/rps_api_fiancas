<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Valide as credenciais
        $credentials = [
            'email'    => $request->input('email'),
            'password' => $request->input('senha'),
        ];

        if ($token = JWTAuth::attempt($credentials)) {
            // Aqui você pode adicionar dados personalizados ao payload
            $user = auth()->user();

            $customClaims = [
                'user_role'        => $user->nivel,
                'user_permissions' => $user->permissoes,
            ];

            // Gerar um novo token com os dados personalizados
            $token = JWTAuth::claims($customClaims)->attempt($credentials);

            return response()->json([
                'token' => $token,
                'user'  => $user,
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
