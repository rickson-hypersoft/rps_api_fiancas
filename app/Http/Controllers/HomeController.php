<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Propostal\Propostal;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, string | int $idImobiliaria)
    {
        $contratos = Propostal::selectRaw('CONTRATO_STATUS, COUNT(*) as total')
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->groupBy('CONTRATO_STATUS')
            ->get();

        $propostas = Propostal::selectRaw('PROPOSTA_STATUS, COUNT(*) as total')
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->where('PROPOSTA_STATUS', '<>', 'Rascunho')
            ->groupBy('PROPOSTA_STATUS')
            ->get();

        return response()->json([
            'contratos' => $contratos,
            'propostas' => $propostas,
        ]);
    }
}
