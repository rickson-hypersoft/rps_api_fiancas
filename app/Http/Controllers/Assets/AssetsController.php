<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Http\Resources\Propostal\PropostalIndexResource;
use App\Models\Propostal\Propostal;

use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function index(Request $request, string | int $idImobiliaria)
    {
        if (!$idImobiliaria) {
            return response()->json(['success' => false, 'message' => 'ID da imobiliária é obrigatório.'], 400);
        }

        $query = Propostal::query()
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->whereNotNull('CONTRATO_STATUS');

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('CONTRATO_STATUS', $request->get('status'));
        }

        // Filtro por nome (case-insensitive)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'like', "%$search%")
                    ->orWhereRaw('LOWER(PESSOA_NOME) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhere('PESSOA_DOC', 'like', "%$search%")
                    ->orWhere('IMOVEL_TAG', 'like', "%$search%");
            });
        }

        if ($request->filled('created_at')) {
            $query->where('DATA', $request->input('created_at'));
        }

        // Ordenação e paginação
        $assets = $query->orderBy('ID', 'desc')->paginate();

        $contratos = Propostal::selectRaw('CONTRATO_STATUS, COUNT(*) as total')
            ->where('ID_IMOBILIARIA', $idImobiliaria)
            ->groupBy('CONTRATO_STATUS')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PropostalIndexResource::collection($assets),
            'contratos' => $contratos,
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'total_pages' => $assets->lastPage(),
                'total' => $assets->total()
            ]
        ]);
    }


    public function find(string $linkHash)
    {
        $query = Propostal::where('LINK_HASH', '=', $linkHash)->firstOrFail();

        $propostal = new PropostalIndexResource($query);

        return response()->json(['data' => $propostal]);
    }

    public function findAsset(string $id)
    {
        $query = Propostal::where('ID', '=', $id)->firstOrFail();

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
