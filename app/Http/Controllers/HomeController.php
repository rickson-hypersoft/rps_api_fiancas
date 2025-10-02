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
                WHEN PROPOSTA_STATUS = 'Reprovado' THEN 'Reprovado'
                WHEN PROPOSTA_STATUS IN ('Pendentes', 'Rascunho') THEN 'Pendente'
                ELSE 'Outro'
            END AS PROPOSTA_STATUS,
            COUNT(*) AS total
        ");

        if ($idImobiliaria !== '' && $idImobiliaria !== '0' && $idImobiliaria !== 0 && $idImobiliaria !== null) {
            $propostasQuery->where('ID_IMOBILIARIA', $idImobiliaria);
        }

        $propostasRaw = $propostasQuery->groupBy('PROPOSTA_STATUS')->get();

        // ---------- CONTRATOS ----------
        $contratosQuery = DB::table('PROPOSTAS')
            ->selectRaw("
        CASE
            WHEN CONTRATO_STATUS = 'Ativo' THEN 'Ativo'
            WHEN CONTRATO_STATUS LIKE 'Pendente%' THEN 'Pendente'
            WHEN CONTRATO_STATUS = 'Reprovada' THEN 'Em renovação'
            WHEN CONTRATO_STATUS = 'Cancelado' THEN 'Cancelado'
            ELSE 'Outro'
        END AS CONTRATO_STATUS,
        COUNT(*) AS total
    ");

        if ($idImobiliaria !== '' && $idImobiliaria !== '0' && $idImobiliaria !== 0 && $idImobiliaria !== null) {
            $contratosQuery->where('ID_IMOBILIARIA', $idImobiliaria);
        }

        $contratosRaw = $contratosQuery->groupBy('CONTRATO_STATUS')->get();

        // ---------- PROPOSTAS CARD ----------
        $propostasCardQuery = Propostal::query();

        if ($idImobiliaria !== '' && $idImobiliaria !== '0' && $idImobiliaria !== 0 && $idImobiliaria !== null) {
            $propostasCardQuery->where('ID_IMOBILIARIA', $idImobiliaria);
        }
        $propostasCard = $propostasCardQuery->orderBy('ID', 'DESC')->get();

        return response()->json([
            'contratos'     => $contratosRaw,
            'propostas'     => $propostasRaw,
            'propostasCard' => PropostalIndexResource::collection($propostasCard),
        ]);
    }
}
