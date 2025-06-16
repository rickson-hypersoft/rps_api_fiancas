<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request, string | int | null $idImobiliaria = null)
    {
        // ---------- PROPOSTAS ----------
        $propostasQuery = DB::table('PROPOSTAS')
            ->selectRaw("
            CASE
                WHEN PROPOSTA_STATUS = 'Aprovado' THEN 'Aprovado'
                WHEN PROPOSTA_STATUS = 'Cancelado' THEN 'Cancelado'
                WHEN PROPOSTA_STATUS = 'Reprovada' THEN 'Reprovado'
                WHEN PROPOSTA_STATUS IN ('Pendentes', 'Rascunho') THEN 'Pendente'
                ELSE 'Outro'
            END AS PROPOSTA_STATUS,
            COUNT(*) AS total
        ");

        if ($idImobiliaria) {
            $propostasQuery->where('ID_IMOBILIARIA', $idImobiliaria);
        }

        $propostasRaw = $propostasQuery->groupBy('PROPOSTA_STATUS')->get();

        // ---------- CONTRATOS ----------
        $contratosQuery = DB::table('PROPOSTAS')
            ->selectRaw("
            CASE
                WHEN CONTRATO_STATUS = 'Ativo' AND ANX_CONTRATO = 1 AND ANX_VISTORIA = 1 AND ANX_APOLICE = 1 THEN 'Ativo'
                WHEN CONTRATO_STATUS = 'Ativo' AND (ANX_CONTRATO = 0 OR ANX_VISTORIA = 0 OR ANX_APOLICE = 0) THEN 'Pendente'
                WHEN PROPOSTA_STATUS = 'Reprovada' THEN 'Em renovação'
                WHEN PROPOSTA_STATUS IN ('Pendentes', 'Rascunho') THEN 'Cancelado'
                ELSE 'Outro'
            END AS CONTRATO_STATUS,
            COUNT(*) AS total
        ");

        if ($idImobiliaria) {
            $contratosQuery->where('ID_IMOBILIARIA', $idImobiliaria);
        }

        $contratosRaw = $contratosQuery->groupBy('CONTRATO_STATUS')->get();

        // ---------- PROPOSTAS CARD ----------
        $propostasCardQuery = Propostal::query();

        if ($idImobiliaria) {
            $propostasCardQuery->where('ID_IMOBILIARIA', $idImobiliaria);
        }
        $propostasCard = $propostasCardQuery->orderBy('ID', 'ASC')->get();

        return response()->json([
            'contratos'     => $contratosRaw,
            'propostas'     => $propostasRaw,
            'propostasCard' => PropostalIndexResource::collection($propostasCard),
        ]);
    }
}
