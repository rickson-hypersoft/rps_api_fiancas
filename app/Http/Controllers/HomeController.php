<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request, string | int $idImobiliaria)
    {
        // ---------- PROPOSTAS ----------
        $propostasRaw = DB::table('PROPOSTAS')
            ->selectRaw("
            CASE
                WHEN PROPOSTA_STATUS = 'Aprovado' THEN 'Aprovado'
                WHEN PROPOSTA_STATUS = 'Cancelada' THEN 'Cancelado'
                WHEN PROPOSTA_STATUS = 'Reprovada' THEN 'Reprovado'
                WHEN PROPOSTA_STATUS IN ('Pendentes', 'Rascunho') THEN 'Pendente'
                ELSE 'Outro'
            END AS PROPOSTA_STATUS,
            COUNT(*) AS total
        ")
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->groupBy('PROPOSTA_STATUS')
            ->get();

        // ---------- CONTRATOS ----------
        $contratosRaw = DB::table('PROPOSTAS')
            ->selectRaw("
            CASE
                WHEN CONTRATO_STATUS = 'Ativo' AND ANX_CONTRATO = 1 AND ANX_VISTORIA = 1 AND ANX_APOLICE = 1 THEN 'Ativo'
                WHEN CONTRATO_STATUS = 'Ativo' AND (ANX_CONTRATO = 0 OR ANX_VISTORIA = 0 OR ANX_APOLICE = 0) THEN 'Pendente'
                WHEN PROPOSTA_STATUS = 'Reprovada' THEN 'Em renovação'
                WHEN PROPOSTA_STATUS IN ('Pendentes', 'Rascunho') THEN 'Cancelado'
                ELSE 'Outro'
            END AS CONTRATO_STATUS,
            COUNT(*) AS total
        ")
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->groupBy('CONTRATO_STATUS')
            ->get();

        $propostasCard = Propostal::where('ID_IMOBILIARIA', '=', $idImobiliaria)->get();

        return response()->json([
            'contratos'     => $contratosRaw,
            'propostas'     => $propostasRaw,
            'propostasCard' => PropostalIndexResource::collection($propostasCard),
        ]);
    }
}
