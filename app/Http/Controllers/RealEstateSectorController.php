<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\RealEstateSectorResource;
use App\Models\RealEstateSector;
use App\Models\RealEstateSectorSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RealEstateSectorController extends Controller
{
    public function index(): JsonResponse
    {
        $realEstateSectors = RealEstateSector::with('setup')->get();

        return response()->json(RealEstateSectorResource::collection($realEstateSectors));
    }

    public function store(Request $request): JsonResponse
    {
        $requestSanitize = $request->all();
        $requestSanitize = $this->sanitizeData($request->all(), ['cnpj', 'telefone', 'cep']);

        $validator = Validator::make($requestSanitize, [
            'razao'           => 'required|string|max:100',
            'fantasia'        => 'required|string|max:100',
            'creci'           => 'required|string|max:50',
            'cnpj'            => 'required|string|max:14|unique:IMOBILIARIAS,CNPJ',
            'endereco'        => 'nullable|string|max:100',
            'numero'          => 'nullable|string|max:30',
            'bairro'          => 'nullable|string|max:100',
            'cidade'          => 'nullable|string|max:100',
            'uf'              => 'nullable|string|max:2',
            'cep'             => 'nullable|string|max:10',
            'complemento'     => 'nullable|string|100',
            'telefone'        => 'nullable|string|max:16',
            'contato'         => 'nullable|string|max:100',
            'cargo'           => 'nullable|string|max:100',
            'representante'   => 'nullable|string|max:100',
            'email'           => 'nullable|string|max:150',
            'tipo_pagamento'  => 'nullable|string|max:30',
            'taxa_padrao'     => 'nullable|numeric|between:0,9999999.99',
            'custo_saida'     => 'nullable|numeric|between:0,9999999.99',
            'cobertura_total' => 'nullable|numeric|between:0,9999999.99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $realEstateSector = $validator->validated();
        $realEstateSector = $this->convertIsoAndTransformUpperCase($realEstateSector);

        $realEstateSector = RealEstateSector::create($realEstateSector);

        return response()->json([
            "success" => true,
            "message" => "Imobiliária criada com sucesso!",
            "data"    => new RealEstateSectorResource($realEstateSector),
        ], 201);
    }

    public function update(int | string $id, Request $request): JsonResponse
    {
        $realEstateSector = RealEstateSector::query()->where("ID", "=", $id)->firstOrFail();

        $requestSanitize = $request->all();
        $requestSanitize = $this->sanitizeData($request->all(), ['telefone', 'cep']);

        $validator = Validator::make($requestSanitize, [
            'razao'           => 'required|string|max:100',
            'fantasia'        => 'required|string|max:100',
            'creci'           => 'required|string|max:50',
            'endereco'        => 'nullable|string|max:100',
            'numero'          => 'nullable|string|max:30',
            'bairro'          => 'nullable|string|max:100',
            'cidade'          => 'nullable|string|max:100',
            'uf'              => 'nullable|string|max:2',
            'cep'             => 'nullable|string|max:10',
            'complemento'     => 'nullable|string|100',
            'telefone'        => 'nullable|string|max:16',
            'contato'         => 'nullable|string|max:100',
            'cargo'           => 'nullable|string|max:100',
            'representante'   => 'nullable|string|max:100',
            'email'           => 'nullable|string|max:150',
            'tipo_pagamento'  => 'nullable|string|max:30',
            'taxa_padrao'     => 'nullable|numeric|between:0,9999999.99',
            'custo_saida'     => 'nullable|numeric|between:0,9999999.99',
            'cobertura_total' => 'nullable|numeric|between:0,9999999.99',
        ]);

        if ($validator->failed()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $realEstateSectorData = $validator->validated();
        $realEstateSectorData = $this->convertIsoAndTransformUpperCase($realEstateSectorData);

        $realEstateSector->update($realEstateSectorData);

        return response()->json([
            "success" => true,
            "message" => "Imobiliária atualizada com sucesso!",
            "data"    => new RealEstateSectorResource($realEstateSector),
        ], 200);
    }

    public function storeSetup(string | int $idRealEstateSector, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'taxa'  => 'nullable|numeric|between:0,9999999.99',
            'ativo' => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $realEstateSectorSetup = $validator->validated();
        $realEstateSectorSetup = $this->convertIsoAndTransformUpperCase($realEstateSectorSetup);

        $realEstateSectorSetup['ID_IMOBILIARIA'] = $idRealEstateSector;

        $realEstateSectorSetup = RealEstateSectorSetup::create($realEstateSectorSetup);

        return response()->json([
            "success" => true,
            "message" => "Imobiliária Setup criado com sucesso!",
        ], 201);
    }

    public function updateSetup(string | int $id, Request $request): JsonResponse
    {
        $realEstateSectorSetup = RealEstateSectorSetup::query()->where("ID", "=", $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'taxa'  => 'nullable|numeric|between:0,9999999.99',
            'ativo' => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->failed()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $realEstateSectorSetupData = $validator->validated();
        $realEstateSectorSetupData = $this->convertIsoAndTransformUpperCase($realEstateSectorSetupData);

        $realEstateSectorSetup->update($realEstateSectorSetupData);

        return response()->json([
            "success" => true,
            "message" => "Imobiliária Setup atualizado com sucesso!",
        ], 200);
    }
}
