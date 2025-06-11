<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;

use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function index(Request $request)
    {
        $idImobiliaria = $request->get('id_imobiliaria');

        if (!$idImobiliaria) {
            return response()->json(['success' => false, 'message' => 'ID da imobiliária é obrigatório.'], 400);
        }

        $query = Propostal::query()
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->whereNotNull('CONTRATO_STATUS');

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('PROPOSTA_CREDITO_STATUS', $request->get('status'));
        }

        // Filtro por nome (case-insensitive)
        if ($request->filled('nome')) {
            $nome = strtolower($request->get('nome'));
            $query->whereRaw('LOWER(NOME_COMPLETO) LIKE ?', ["%{$nome}%"]);
        }

        // Filtro por CNJ (case-insensitive)
        if ($request->filled('cnj')) {
            $cnj = strtolower($request->get('cnj'));
            $query->whereRaw('LOWER(CNJ) LIKE ?', ["%{$cnj}%"]);
        }

        // Ordenação e paginação
        $resultados = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => PropostalIndexResource::collection($resultados),
            'pagination' => [
                'current_page' => $resultados->currentPage(),
                'total_pages' => $resultados->lastPage(),
                'total' => $resultados->total()
            ]
        ]);
    }


    public function find(string $linkHash)
    {
        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        return response()->json(['data' => $propostal]);
    }

    public function faceId(Request $request, string $linkHash)
    {
        $asset = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();
        $data  = ['PROPOSTA_CREDITO_STATUS' => 'Aguardando Pagamento', 'FACIAL' => 1];

        $asset->update($data);

        return response()->json("Facial atualizda");
    }
}