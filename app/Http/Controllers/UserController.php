<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = UserResource::collection(User::all());

        return response()->json(['users' => $users]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'USUARIO'        => 'required|string|max:30',
            'SENHA'          => 'required|string|max:255',
            'NOME'           => 'required|string|max:50',
            'EMAIL'          => 'nullable|string|max:150',
            'CPF'            => 'nullable|string|max:11|unique:USUARIOS,CPF',
            'TELEFONE'       => 'nullable|string|max:16',
            'NIVEL'          => 'nullable|string|max:50',
            'CATEGORIA'      => 'nullable|string|max:50',
            'ID_IMOBILIARIA' => 'nullable|numeric',
            'ATIVO'          => 'nullable|numeric',
            'PERMISSOES'     => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $users          = $validator->validated();
        $users['SENHA'] = Hash::make($users['SENHA']);

        User::create($users);

        return response()->json([
            "success" => true,
            "message" => "Usuário criado com sucesso!",
            "user"    => $users["USUARIO"],
        ], 201);
    }
}
