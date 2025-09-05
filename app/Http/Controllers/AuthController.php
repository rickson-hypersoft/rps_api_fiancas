<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Http\Resources\RealEstateSectorResource;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\RealEstateSector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $loginDataRaw  = $request->input('login');
        $loginPassword = $request->input('password');
        $remember      = $request->boolean('remember', false);

        if (! is_string($loginDataRaw)) {
            return response()->json(['success' => false, 'message' => 'Login inválido!'], 422);
        }

        $loginData = $this->sanitizeInput($loginDataRaw);

        if ($loginData === null) {
            return response()->json(['success' => false, 'message' => 'Login inválido!'], 422);
        }

        $loginField = $this->getAuthenticateData($loginData);

        if ($loginField === 'EMAIL') {
            $loginValue = $loginData; // Mantém como está (não converte, nem maiúscula)
        } else {
            $loginValue = mb_convert_encoding(mb_strtoupper($loginData, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
        }

        $credentials = [
            $loginField => $loginValue,
            'password'  => $loginPassword,
        ];

        if ($token = JWTAuth::attempt($credentials)) {
            $user = auth('api')->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Usuário não autenticado!'], 401);
            }

            $ttl = $remember ? 43200 : config('jwt.ttl');

            $customClaims = [
                'user_category'    => is_string($user->CATEGORIA) ? mb_convert_encoding($user->CATEGORIA, 'UTF-8', 'ISO-8859-1') : null,
                'user_role'        => is_string($user->NIVEL) ? mb_convert_encoding($user->NIVEL, 'ISO-8859-1', 'UTF-8') : null,
                'user_permissions' => is_string($user->PERMISSOES) ? mb_convert_encoding($user->PERMISSOES, 'ISO-8859-1', 'UTF-8') : null,
            ];

            JWTAuth::factory()->setTTL($ttl);

            $token = JWTAuth::claims($customClaims)->attempt($credentials);

            $company          = Company::find(1);
            $realEstateSector = RealEstateSector::find($user->ID_IMOBILIARIA);

            $category = mb_convert_encoding($user->CATEGORIA, 'UTF-8', 'ISO-8859-1');

            return response()->json([
                'token'                     => $token,
                'expires_in'                => $ttl * 60,
                'user'                      => new UserResource($user),
                'realEstateSectorOrCompany' => $category == 'Imobiliária' ?
                    new RealEstateSectorResource($realEstateSector) :
                    new CompanyResource($company),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciais inválidas!'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();

            if (! $token) {
                return response()->json(['success' => false, 'message' => 'Token inválido.'], 400);
            }

            JWTAuth::invalidate();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso!',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao tentar realizar logout: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getAuthenticateData(string $authenticate): string
    {
        if (str_contains($authenticate, "@")) {
            return 'EMAIL';
        }

        if (ctype_digit($authenticate)) {
            return 'CPF';
        }

        return 'USUARIO';
    }
}