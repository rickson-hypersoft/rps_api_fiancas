<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 4);

        $users = User::orderBy('NOME', 'ASC')->paginate($perPage);

        return UserResource::collection($users)
            ->response()
            ->setStatusCode(200);
    }

    public function find(int | string $id): JsonResponse
    {
        $user = User::where('ID', $id)->firstOrFail();

        return response()->json(["data" => new UserResource($user)]);
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

        User::create($users);

        return response()->json([
            "success" => true,
            "message" => "Usuário criado com sucesso!",
            "data"    => is_string($users['USUARIO']) ? mb_convert_encoding($users['USUARIO'], 'UTF-8', 'ISO-8859-1') : $users['USUARIO'],
        ], 201);
    }

    public function update(string | int $id, Request $request): JsonResponse
    {
        $user = User::query()->where("ID", "=", $id)->firstOrFail();

        $requestSanitize = $this->sanitizeData($request->all(), ['cpf', 'telefone']);

        $validator = Validator::make($requestSanitize, [
            'usuario' => [
                'required',
                'string',
                'max:30',
                Rule::unique('USUARIOS', 'USUARIO')->ignore($id, 'ID'),
            ],
            'nome'  => 'required|string|max:50',
            'email' => [
                'required',
                'string',
                'max:150',
                Rule::unique('USUARIOS', 'EMAIL')->ignore($id, 'ID'),
            ],
            'cpf' => [
                'required',
                'string',
                'max:11',
                Rule::unique('USUARIOS', 'CPF')->ignore($id, 'ID'),
            ],
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

        $userData = $validator->validated();
        $userData = $this->convertIsoAndTransformUpperCase($userData);

        $user->update($userData);

        return response()->json([
            "success" => true,
            "message" => "Usuário atualizado com sucesso!",
            "data"    => new UserResource($user),
        ], 200);
    }
}
