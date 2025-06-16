<?php

declare(strict_types=1);

namespace App\Http\Controllers\Propostal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropostalController extends Controller
{
    public function index(Request $request, int | string $idRealEstateSector): JsonResponse
    {
        $query = Propostal::where('ID_IMOBILIARIA', '=', $idRealEstateSector);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%$search%")
                    ->orWhereRaw('LOWER(PESSOA_NOME) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(PESSOA_FANTASIA) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhere('PESSOA_DOC', 'like', "%$search%")
                    ->orWhere('IMOVEL_TAG', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('PROPOSTA_STATUS', $request->input('status'));
        }

        if ($request->filled('created_at')) {
            $query->where('DATA', $request->input('created_at'));
        }

        $propostals = $query->orderBy('ID', 'desc')->get();

        return response()->json(['data' => PropostalIndexResource::collection($propostals)]);
    }

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
        }

        return $mixed;
    }

    public function find(string | int $id): JsonResponse
    {
        if (is_numeric($id)) {
            $propostal = Propostal::where('ID', '=', $id)->firstOrFail();
        } else {
            $propostal = Propostal::where('LINK_HASH', '=', $id)->firstOrFail();
        }

        $propostal = new PropostalIndexResource($propostal);

        return response()->json(
            $propostal
        );
    }

    public function store(Request $request): JsonResponse
    {
        $requestSanitize = $this->sanitizeData($request->all(), ['pessoa_doc', 'imovel_cep', 'pessoa_telefone']) ?? [];

        $validator = Validator::make($requestSanitize, [
            'id_imobiliaria'          => 'required|numeric',
            'pessoa_tipo'             => 'required|string|max:2',
            'pessoa_doc'              => 'required|string|max:14',
            'pessoa_nome'             => 'required|string|max:100',
            'imovel_tipo'             => 'required|string|max:1',
            'imovel_cep'              => 'required|string|max:16',
            'imovel_aluguel'          => 'required|numeric|between:0,9999999.99',
            'imovel_condominio'       => 'required|numeric|between:0,9999999.99',
            'imovel_taxas'            => 'required|numeric|between:0,9999999.99',
            'imovel_estado'           => 'nullable|string|max:50',
            'imovel_cidade'           => 'nullable|string|max:100',
            'proposta_total_valor'    => 'nullable|numeric|between:0,9999999.99',
            'proposta_total_parc'     => 'nullable|numeric|between:0,9999999.99',
            'proposta_setup_valor'    => 'nullable|numeric|between:0,9999999.99',
            'proposta_setup_parc'     => 'nullable|numeric|between:0,9999999.99',
            'imovel_endereco'         => 'nullable|string|max:50',
            'imovel_bairro'           => 'nullable|string|max:50',
            'imovel_numero'           => 'nullable|string|max:50',
            'imovel_complemento'      => 'nullable|string|max:50',
            'pessoa_email'            => 'nullable|string|max:50',
            'pessoa_telefone'         => 'nullable|string|max:50',
            'imovel_ramo_atv'         => 'nullable|string|max:100',
            'imovel_tag'              => 'nullable|string|max:100',
            'imovel_subtipo'          => 'nullable|string|max:50',
            'proposta_tipo_pagador'   => 'nullable|string|max:50',
            'data_nascimento'         => 'nullable|date',
            'proposta_status'         => 'nullable|string|max:50',
            'proposta_credito_status' => 'nullable|string|max:50',
            'contrato_status'         => 'nullable|string|max:50',
            'observacao'              => 'nullable|string|max:2000',
            'data'                    => 'nullable|date',
            'hora'                    => 'nullable',
            'data_ultima_atualizacao' => 'nullable|date',
            'hora_ultima_atualizacao' => 'nullable',
            'anx_contrato'            => 'nullable|numeric|between:0,1',
            'anx_vistoria'            => 'nullable|numeric|between:0,1',
            'anx_apolice'             => 'nullable|numeric|between:0,1',
            'motivo'                  => 'nullable|string|max:255',
            'motivo_explicacao'       => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $propostalData = $validator->validated();

        if (isset($requestSanitize['id'])) {
            if ($requestSanitize['id'] != "null") {
                $propostalData['id'] = $requestSanitize['id'];
            }
        }

        $propostalData = $this->convertIsoAndTransformUpperCase($propostalData);

        if (! empty($propostalData['ID'])) {
            $propostal = Propostal::query()->where("ID", "=", $propostalData['ID'])->firstOrFail();
            $propostal->update($propostalData);
        } else {
            $propostal = Propostal::create($propostalData);
        }

        return response()->json([
            "success" => true,
            "message" => isset($propostal->ID) ? "Proposta atualizada com sucesso!" : "Proposta criada com sucesso!",
            "data"    => new PropostalIndexResource($propostal),
        ], 201);
    }

    public function canceled(Request $request, string | int $id): JsonResponse
    {
        $propostal = Propostal::query()->where("ID", "=", $id)->firstOrFail();

        $requestSanitize = $this->sanitizeData($request->all(), []) ?? [];

        $validator = Validator::make($requestSanitize, [
            'proposta_status'         => 'nullable|string|max:50',
            'proposta_credito_status' => 'nullable|string|max:50',
            'contrato_status'         => 'nullable|string|max:50',
            'motivo'                  => 'nullable|string|max:255',
            'motivo_explicacao'                  => 'nullable|string|max:255',
            'data_ultima_atualizacao' => 'nullable|date',
            'hora_ultima_atualizacao' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $propostalData         = $validator->validated();
        $propostalData['data'] = date('Y-m-d');
        $propostalData['hora'] = date('H:i:s');

        if (isset($requestSanitize['id'])) {
            if ($requestSanitize['id'] != "null") {
                $propostalData['id'] = $requestSanitize['id'];
            }
        }

        $propostalData = $this->convertIsoAndTransformUpperCase($propostalData);

        $propostal->update($propostalData);

        return response()->json([
            "success" => true,
            "message" => "Proposta cancelada com sucesso!",
        ], 200);
    }

    public function hashLink(string | int $id): JsonResponse
    {
        $propostal     = Propostal::query()->where("ID", "=", $id)->firstOrFail();
        $propostalData = ['LINK_HASH' => substr(hash('sha1', $id . config('app.key')), 0, 12)];
        $propostal->update($propostalData);

        return response()->json([
            "success" => true,
            "message" => "Hash feito com sucesso!",
        ], 201);
    }
}
