<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $loginData     = $this->sanitizeInput($request->input('login'));
        $loginPassword = $request->input('password');

        $credentials = [
            $this->getAuthenticateData($loginData) => utf8_decode($loginData),
            'password'                             => $loginPassword,
        ];

        if ($token = JWTAuth::attempt($credentials)) {
            $user = auth('api')->user();

            $customClaims = [
                'user_category'    => mb_convert_encoding($user->CATEGORIA, 'UTF-8', 'ISO-8859-1'),
                'user_role'        => mb_convert_encoding($user->NIVEL, 'ISO-8859-1', 'UTF-8'),
                'user_permissions' => mb_convert_encoding($user->PERMISSOES, 'ISO-8859-1', 'UTF-8'),
            ];

            $token = JWTAuth::claims($customClaims)->attempt($credentials);

            return response()->json([
                'token' => $token,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciais inválidas!'], 401);
    }

    private function getAuthenticateData(string $authenticate): string
    {
        if (strpos($authenticate, "@") != false) {
            return 'EMAIL';
        }

        if (ctype_digit($authenticate)) {
            return 'CPF';
        }

        return 'USUARIO';
    }
}
