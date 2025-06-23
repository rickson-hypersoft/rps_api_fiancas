<?php

declare(strict_types = 1);

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
        $perPage = $request->get('per_page', 10);
        $query   = User::orderBy('NOME', 'ASC');

        if ($request->filled('search')) {
            $searchRaw = $request->input('search');

            $query->where(function ($q) use ($searchRaw): void {
                if (is_numeric($searchRaw)) {
                    // Busca pelo CPF, já que é numérico (não precisa converter maiúscula/encoding)
                    $q->whereRaw('CAST(CPF AS VARCHAR(20)) LIKE ?', ['%' . $searchRaw . '%']);
                } else {
                    // Busca por nome e usuário, com conversão para maiúsculo e encoding ISO-8859-1
                    $searchUpper = mb_strtoupper((string) $searchRaw, 'UTF-8');
                    $searchIso   = mb_convert_encoding($searchUpper, 'ISO-8859-1', 'UTF-8');

                    $q->whereRaw('UPPER(NOME) LIKE ?', ['%' . $searchIso . '%'])
                        ->orWhereRaw('UPPER(USUARIO) LIKE ?', ['%' . $searchIso . '%']);
                }
            });
        }

        $users = $query->paginate($perPage);

        return UserResource::collection($users)
            ->response()
            ->setStatusCode(200);
    }

    public function indexUserRealEstateSector(string | int $idImobiliaria, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 7);

        $query = User::where('ID_IMOBILIARIA', $idImobiliaria);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(NOME) LIKE ?', ['%' . strtolower((string) $search) . '%'])
                    ->orWhereRaw('LOWER(USUARIO) LIKE ?', ['%' . strtolower((string) $search) . '%'])
                    ->orWhereRaw('LOWER(CPF) LIKE ?', ['%' . strtolower((string) $search) . '%']);
            });
        }

        $users = $query->orderBy('NOME', 'ASC')
            ->paginate($perPage);

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

        if (isset($users['usuario'])) {
            $users['usuario'] = mb_strtoupper($users['usuario']);
        }

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
            'ativo'          => 'nullable|numeric|between:0,1',
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

    public function destroy(int | string $id): JsonResponse
    {
        $user = User::query()->where("ID", "=", $id)->first();

        if (! $user) {
            return response()->json([
                "success" => false,
                "message" => "Usuário não encontrada.",
            ], 404);
        }

        $user->delete();

        return response()->json([
            "success" => true,
            "message" => "Usuário deletado com sucesso.",
        ], 200);
    }
}
