<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\RealEstateSectorResource;
use App\Models\RealEstateSector;
use App\Models\RealEstateSectorSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RealEstateSectorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);

        $query = RealEstateSector::with('setups');

        if ($request->filled('search')) {
            $search      = $request->input('search');
            $searchUpper = mb_strtoupper($search, 'UTF-8');
            $searchIso   = mb_convert_encoding($searchUpper, 'ISO-8859-1', 'UTF-8');
            $isNumeric   = is_numeric($search);
            $length      = strlen($search);

            // Se parecer com um CNPJ
            if (preg_match('/^\d{14}$/', $search)) {
                // não precisa de strtolower aqui, pois é só número
                $search = substr($search, 0, 14);
            }

            $query->where(function ($q) use ($search, $searchIso, $isNumeric, $length) {
                if ($isNumeric && $length >= 11 && $length <= 14) {
                    // Busca por CPF/CNPJ
                    $q->orWhereRaw('CAST(CNPJ AS VARCHAR(20)) LIKE ?', ['%' . $search . '%']);
                } else {
                    // Nome e Fantasia (case-insensitive)
                    $q->orWhereRaw('UPPER(RAZAO) LIKE ?', ['%' . $searchIso . '%'])
                        ->orWhereRaw('UPPER(FANTASIA) LIKE ?', ['%' . $searchIso . '%']);
                }
            });
        }

        $realEstateSectors = $query->orderBy('RAZAO', 'ASC')->paginate($perPage);

        return RealEstateSectorResource::collection($realEstateSectors)
            ->response()
            ->setStatusCode(200);
    }

    public function listall(): JsonResponse
    {
        // $realEstateSectors = RealEstateSector::where('ATIVO', '=', 1);
        $realEstateSectors = RealEstateSector::all();

        return response()->json(['data' => RealEstateSectorResource::collection($realEstateSectors)]);
    }

    public function find(string | int $id): JsonResponse
    {
        $realEstateSector = RealEstateSector::with('setups')->where('ID', $id)->firstOrFail();

        return response()->json(["data" => new RealEstateSectorResource($realEstateSector)]);
    }

    public function findSetup(string | int $id): JsonResponse
    {
        $realEstateSectors = RealEstateSectorSetup::where('ID_IMOBILIARIA', $id)->get();

        $data = $realEstateSectors->map(function ($item) {
            return [
                'id'             => $item->ID,
                'id_imobiliaria' => $item->ID_IMOBILIARIA,
                'taxa'           => $item->TAXA !== null
                    ? 'R$ ' . number_format(floatval($item->TAXA), 2, ',', '') . ''
                    : null,
                'taxa_formatada' => $item->TAXA > 0
                    ? 'R$ ' . number_format(floatval($item->TAXA), 2, ',', '') . ' em até 3x de R$ ' . number_format(floatval($item->TAXA) / 3, 2, ',', '')
                    : null,
                'ativo' => $item->ATIVO,
            ];
        });

        return response()->json(['data' => $data]);
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
            'complemento'     => 'nullable|string|max:100',
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
            'razao'    => 'required|string|max:100',
            'fantasia' => 'required|string|max:100',
            'creci'    => 'required|string|max:50',
            'cnpj'     => [
                'nullable',
                'string',
                'max:14',

            ],
            'endereco'        => 'nullable|string|max:100',
            'numero'          => 'nullable|string|max:30',
            'bairro'          => 'nullable|string|max:100',
            'cidade'          => 'nullable|string|max:100',
            'uf'              => 'nullable|string|max:2',
            'cep'             => 'nullable|string|max:10',
            'complemento'     => 'nullable|string|max:100',
            'telefone'        => 'nullable|string|max:16',
            'contato'         => 'nullable|string|max:100',
            'cargo'           => 'nullable|string|max:100',
            'representante'   => 'nullable|string|max:100',
            'email'           => 'nullable|string|max:150',
            'tipo_pagamento'  => 'nullable|string|max:30',
            'taxa_padrao'     => 'nullable|numeric|between:0,9999999.99',
            'custo_saida'     => 'nullable|numeric|between:0,9999999.99',
            'cobertura_total' => 'nullable|numeric|between:0,9999999.99',
            'ativo'           => 'nullable|numeric|between:0,1',
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
            'taxa' => [
                'nullable',
                'numeric',
                'between:0,9999999.99',
                Rule::unique('IMOBILIARIAS_SETUP', 'TAXA')->where(function ($query) use ($request) {
                    return $query->where('ID_IMOBILIARIA', $request->input('id_imobiliaria'));
                })
                    ->ignore($request->input('id'), 'ID'),
            ],
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
            "message" => "Configuração da Imobiliária criado com sucesso!",
        ], 201);
    }

    public function updateSetup(string | int $id, Request $request): JsonResponse
    {
        $realEstateSectorSetup = RealEstateSectorSetup::query()->where("ID", "=", $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'taxa' => [
                'nullable',
                'numeric',
                'between:0,9999999.99',
                Rule::unique('IMOBILIARIAS_SETUP', 'TAXA')->where(function ($query) use ($request) {
                    return $query->where('ID_IMOBILIARIA', $request->input('id_imobiliaria'));
                })
                    ->ignore($request->input('id'), 'ID'),
            ],
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
            "message" => "Configuração da Imobiliária atualizado com sucesso!",
        ], 200);
    }

    public function destroy(string | int $id): JsonResponse
    {
        $realEstateSector = RealEstateSector::with('setups')->where('ID', $id)->first();

        if (! $realEstateSector) {
            return response()->json([
                "success" => false,
                "message" => "Imobiliária não encontrada.",
            ], 404);
        }

        // Excluir os setups relacionados primeiro
        $realEstateSector->setups()->delete();

        // Agora exclui a imobiliária
        $realEstateSector->delete();

        return response()->json([
            "success" => true,
            "message" => "Imobiliária e suas configurações excluídas com sucesso!",
        ], 200);
    }
}
