<?php

declare(strict_types=1);

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

            $company = Company::find(1);
            $realEstateSector = RealEstateSector::find($user['id_imobiliaria']);

            return response()->json([
                'token'   => $token,
                'user'    => new UserResource($user),
                'company' => new CompanyResource($company),
                'realEstateSector' => $realEstateSector ? new RealEstateSectorResource($realEstateSector) : []
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciais inválidas!'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

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
        if (strpos($authenticate, "@") != false) {
            return 'EMAIL';
        }

        if (ctype_digit($authenticate)) {
            return 'CPF';
        }

        return 'USUARIO';
    }
}
