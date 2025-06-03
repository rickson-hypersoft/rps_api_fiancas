<?php

declare(strict_types=1);

namespace App\Http\Controllers\Propostal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalResource;
use App\Models\Propostal\Propostal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropostalController extends Controller
{
    public function find(string|int $id)
    {
        $propostal = Propostal::where('ID', '=', $id)->first();
        $propostal = new PropostalResource($propostal);

        return response()->json($propostal);
    }

    public function store(Request $request)
    {
        $requestSanitize = $this->sanitizeData($request->all(), ['pessoa_doc', 'imovel_cep']) ?? [];

        $validator = Validator::make($requestSanitize, [
            'id_imobiliaria'    => 'required|numeric',
            'pessoa_tipo'       => 'required|string|max:2',
            'pessoa_doc'        => 'required|string|max:14',
            'pessoa_nome'       => 'required|string|max:100',
            'imovel_tipo'       => 'required|string|max:1',
            'imovel_cep'        => 'required|string|max:16',
            'imovel_aluguel'    => 'required|numeric|between:0,9999999.99',
            'imovel_condominio' => 'required|numeric|between:0,9999999.99',
            'imovel_taxas'      => 'required|numeric|between:0,9999999.99',
            'imovel_estado'     => 'nullable|string|max:50',
            'imovel_cidade'         => 'nullable|string|max:100',
            'proposta_total_valor'  => 'nullable|numeric|between:0,9999999.99',
            'proposta_total_parc'   => 'nullable|numeric|between:0,9999999.99',
            'proposta_setup_valor'  => 'nullable|numeric|between:0,9999999.99',
            'proposta_setup_parc'   => 'nullable|numeric|between:0,9999999.99',
            'imovel_endereco'     => 'nullable|string|max:50',
            'imovel_bairro'     => 'nullable|string|max:50',
            'imovel_numero'     => 'nullable|string|max:50',
            'imovel_complemento'     => 'nullable|string|max:50',
            'pessoa_email'     => 'nullable|string|max:50',
            'pessoa_telefone'     => 'nullable|string|max:50',
            'imovel_ramo_atv'     => 'nullable|string|max:100',
            'imovel_tag'     => 'nullable|string|max:100',
            'imovel_subtipo'     => 'nullable|string|max:50',
            'proposta_tipo_pagador'     => 'nullable|string|max:50',
            'data_nascimento'     => 'nullable|date',
            'proposta_status'     => 'nullable|string|max:50',
            'proposta_credito_status'     => 'nullable|string|max:50',
            'contrato_status'     => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }

        $propostalData = $validator->validated();
        $propostalData['data'] = date('Y-m-d');
        $propostalData['hora'] = date('H:i:s');

        if (isset($requestSanitize['id'])) {
            if ($requestSanitize['id'] != "null") {
                $propostalData['id'] = $requestSanitize['id'];
            }
        }

        $propostalData = $this->convertIsoAndTransformUpperCase($propostalData);

        if (!empty($propostalData['ID'])) {
            $propostal = Propostal::query()->where("ID", "=", $propostalData['ID'])->firstOrFail();
            $propostal->update($propostalData);
        } else {
            $propostal = Propostal::create($propostalData);
        }

        return response()->json([
            "success" => true,
            "message" => isset($propostalData['id']) ? "Proposta atualizada com sucesso!" : "Proposta criada com sucesso!",
            "data"    => new PropostalResource($propostal),
        ], 201);
    }
}
