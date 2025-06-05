<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = CompanyResource::collection(Company::all());

        return response()->json(["data" => $companies]);
    }

    public function store(Request $request): JsonResponse
    {
        $requestSanitize = $this->sanitizeData($request->all(), ['cnpj', 'cep', 'telefone']) ?? [];

        $validator = Validator::make($requestSanitize, [
            'razao'         => 'required|string|max:100',
            'fantasia'      => 'required|string|max:100',
            'cnpj'          => 'required|string|max:14|unique:EMPRESAS,CNPJ',
            'endereco'      => 'nullable|string|max:100',
            'numero'        => 'nullable|string|max:30',
            'bairro'        => 'nullable|string|max:100',
            'cidade'        => 'nullable|string|max:100',
            'uf'            => 'nullable|string|max:2',
            'cep'           => 'nullable|string|max:10',
            'complemento'   => 'nullable|string|max:100',
            'telefone'      => 'nullable|string|max:16',
            'contato'       => 'nullable|string|max:100',
            'cargo'         => 'nullable|string|max:100',
            'representante' => 'nullable|string|max:100',
            'email'         => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $companies = $this->convertIsoAndTransformUpperCase($validator->validated());

        $company = Company::create($companies);

        return response()->json([
            "success" => true,
            "message" => "Empresa criada com sucesso!",
            "data"    => new CompanyResource($company),
        ], 201);
    }

    public function update(int | string $id, Request $request): JsonResponse
    {
        $company = Company::query()->where("ID", "=", $id)->firstOrFail();

        $requestSanitize = $this->sanitizeData($request->all(), ['cnpj', 'cep', 'telefone']) ?? [];

        $validator = Validator::make($requestSanitize, [
            'razao'    => 'required|string|max:100',
            'fantasia' => 'required|string|max:100',
            'cnpj'     => [
                'required',
                'string',
                'max:14',
                Rule::unique('EMPRESAS', 'CNPJ')->ignore($id, 'ID'),
            ],
            'endereco'      => 'nullable|string|max:100',
            'numero'        => 'nullable|string|max:30',
            'bairro'        => 'nullable|string|max:100',
            'cidade'        => 'nullable|string|max:100',
            'uf'            => 'nullable|string|max:2',
            'cep'           => 'nullable|string|max:10',
            'complemento'   => 'nullable|string|max:100',
            'telefone'      => 'nullable|string|max:16',
            'contato'       => 'nullable|string|max:100',
            'cargo'         => 'nullable|string|max:100',
            'representante' => 'nullable|string|max:100',
            'email'         => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $companies = $validator->validated();
        $companies = $this->convertIsoAndTransformUpperCase($validator->validated());

        $company->update($companies);

        return response()->json([
            "success" => true,
            "message" => "Empresa atualizada com sucesso!",
            "data"    => new CompanyResource($company),
        ], 200);
    }
}
