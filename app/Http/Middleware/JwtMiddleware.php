<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Garantir que o token esteja presente
            $token = JWTAuth::parseToken();

            if (! $token->check()) {
                return response()->json(['error' => 'Token inválido ou expirado'], 401);
            }

            $user = $token->authenticate();

            if (! $user) {
                return response()->json(['error' => 'Usuário não encontrado'], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expirado'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        return $next($request);
    }
}
