<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = UserResource::collection(User::all());

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $requestSanitize = $this->sanitizeData($request->all(), ['cpf', 'telefone']);

        $validator = Validator::make($requestSanitize, [
            'usuario'        => 'required|string|max:30|unique:USUARIOS,USUARIO',
            'senha'          => 'required|string|max:255',
            'nome'           => 'required|string|max:50',
            'email'          => 'nullable|string|max:150|unique:USUARIOS,EMAIL',
            'cpf'            => 'nullable|string|max:11|unique:USUARIOS,CPF',
            'telefone'       => 'nullable|string|max:16',
            'nivel'          => 'nullable|string|max:50',
            'categoria'      => 'nullable|string|max:50',
            'id_imobiliaria' => 'nullable|numeric',
            'ativo'          => 'nullable|numeric',
            'permissoes'     => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $users = $validator->validated();
        $users = $this->convertIsoAndTransformUpperCase($users);

        $users['SENHA'] = Hash::make($users['SENHA']);

        User::create($users);

        return response()->json([
            "success" => true,
            "message" => "Usuário criado com sucesso!",
            "data"    => is_string($users['USUARIO']) ? mb_convert_encoding($users['USUARIO'], 'UTF-8', 'ISO-8859-1') : $users['USUARIO'],
        ], 201);
    }
}
